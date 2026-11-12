<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
$register = require dirname(__DIR__) . '/app/bot/commands.php';

$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('PRAGMA foreign_keys = ON');
foreach (glob(dirname(__DIR__) . '/app/database/migrations/*.php') as $file) {
    $migration = require $file;
    $migration($pdo);
}
$users = new App\Database\Repositories\UserRepository($pdo);
$groups = new App\Database\Repositories\FoodGroupRepository($pdo);
$foods = new App\Database\Repositories\FoodRepository($pdo);
$bot = SergiX44\Nutgram\Testing\FakeNutgram::instance();
$register($bot, $users, $groups, $foods);

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function command(string $input, string|array $expected): void
{
    global $bot;
    $bot->hearText($input)->reply();
    $history = $bot->getRequestHistory();
    check(count($history) === 1, 'Expected one reply: ' . $input);
    $body = urldecode((string) $history[0]['request']->getBody());
    foreach ((array) $expected as $text) {
        check(str_contains($body, $text), 'Unexpected reply to ' . $input . ': ' . $body);
    }
}

check(array_column($groups->index(), 'name') === ['fruits', 'vegetables', 'grains', 'protein', 'dairy', 'fats'], 'Default groups missing');
$seed = require dirname(__DIR__) . '/app/database/migrations/003_seed_food_groups.php';
$seed($pdo);
check(count($groups->index()) === 6, 'Seeding duplicated groups');
command('/fgroup', 'Food group commands:');
command('/help', ['/start', '/hello', '/help', '/food', '/fgroup Index', '/fgroup Create', '/fgroup Read', '/fgroup Update', '/fgroup Delete']);
command('/fgroup Index', ['fruits', '/fgroup Create', '/fgroup Read', '/fgroup Update', '/fgroup Delete']);
command('/fgroup Create "Whole grains"', 'Food group created.');
command('/fgroup Read "WHOLE GRAINS"', 'Whole grains');
command('/fgroup Create "whole grains"', 'already exists');
command('/fgroup Update "Whole grains" "Cereal grains"', 'Food group updated.');
check($groups->findByName('Whole grains') === null, 'Old group name still exists');
command('/fgroup Update "Cereal grains" fruits', 'already exists');
command('/fgroup Delete "Cereal grains"', 'Food group deleted:');
command('/fgroup Read "Cereal grains"', 'not found');
command('/fgroup Create ""', 'Food group commands:');
command('/fgroup Update fruits', 'Food group commands:');
command('/fgroup Index extra', 'Food group commands:');
command('/fgroup Unknown', 'Food group commands:');
command('/fgroup Create "broken', 'Food group commands:');
$fruit = $groups->findByName('fruits');
$pdo->exec("INSERT INTO foods (name, energy, food_group_id) VALUES ('Apple', 52, " . (int) $fruit['id'] . ')');
command('/fgroup Delete fruits', 'used by foods');
check($groups->findByName('fruits') !== null, 'Referenced group was deleted');
command('/fgroup Create "Chef\'s choice"', 'Food group created.');
check($groups->findByName("Chef's choice") !== null, 'Name containing apostrophe not saved');
command('/food', 'Cadastrar alimento:');
command('/food "Maçã verde" 52 FRUITS', ['Alimento cadastrado!', 'Maçã verde', 'Calorias: 52', 'Grupo: fruits']);
$food = $foods->index();
$food = end($food);
check($food['name'] === 'Maçã verde' && (int) $food['energy'] === 52 && (int) $food['food_group_id'] === (int) $fruit['id'], 'Food saved with incorrect data or foreign key');
command('/food "Chef\'s special" 0 "Chef\'s choice"', ['Alimento cadastrado!', 'Calorias: 0', "Grupo: Chef's choice"]);
$count = count($foods->index());
command('/food Banana 89 missing', 'Grupo não encontrado:');
command('/food Banana 89 ' . $fruit['id'], 'Grupo não encontrado:');
foreach (['/food Banana -1 fruits', '/food Banana 2.5 fruits', '/food Banana abc fruits', '/food "" 52 fruits', '/food Banana 52 ""', '/food Banana 52', '/food Banana 52 fruits extra', '/food "broken 52 fruits', '/food Banana 99999999999999999999999999 fruits'] as $invalid) {
    command($invalid, 'Cadastrar alimento:');
}
check(count($foods->index()) === $count, 'Invalid food command inserted rows');
$pdo->exec('DELETE FROM foods');
$pdo->exec('DELETE FROM food_groups');
command('/fgroup Index', ['No food groups registered.', '/fgroup Create', '/fgroup Read', '/fgroup Update', '/fgroup Delete']);
echo "Food group and food command tests passed.\n";
