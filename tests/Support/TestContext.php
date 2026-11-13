<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Database\Repositories\FoodGroupRepository;
use App\Database\Repositories\FoodRepository;
use App\Database\Repositories\UserRepository;
use PDO;
use Closure;
use DateTimeZone;
use App\Database\Repositories\DailyRepository;
use App\Database\Repositories\DailyFoodRepository;
use RuntimeException;
use SergiX44\Nutgram\Testing\FakeNutgram;

final class TestContext
{
    public PDO $pdo;
    public UserRepository $users;
    public FoodGroupRepository $groups;
    public FoodRepository $foods;
    public FakeNutgram $bot;
    public DailyRepository $daily;
    public DailyFoodRepository $entries;

    public function __construct(?Closure $clock = null)
    {
        $root = dirname(__DIR__, 2);
        $register = require $root . '/app/bot/commands.php';
        $this->pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $files = glob($root . '/app/database/migrations/*.php');
        sort($files);
        foreach ($files as $file) {
            $migration = require $file;
            $migration($this->pdo);
        }
        $this->users = new UserRepository($this->pdo);
        $this->groups = new FoodGroupRepository($this->pdo);
        $this->foods = new FoodRepository($this->pdo);
        $this->daily = new DailyRepository($this->pdo);
        $this->entries = new DailyFoodRepository($this->pdo);
        $this->bot = FakeNutgram::instance();
        $register($this->bot, $this->users, $this->groups, $this->foods, $this->daily, $this->entries, new DateTimeZone('America/Recife'), $clock);
    }

    public function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    public function actAs(int $telegramId): void
    {
        $this->users->save($telegramId, 'Test user');
        $this->bot->setCommonUser(\SergiX44\Nutgram\Telegram\Types\User\User::make($telegramId, false, 'Test user'));
    }

    public function command(string $input, string|array $expected): void
    {
        $this->bot->hearText($input)->reply();
        $history = $this->bot->getRequestHistory();
        $this->check(count($history) === 1, 'Expected one reply for: ' . $input);
        $body = urldecode((string) $history[0]['request']->getBody());
        foreach ((array) $expected as $text) {
            $this->check(str_contains($body, $text), "Unexpected reply for {$input}; expected: {$text}; received: {$body}");
        }
    }
}
