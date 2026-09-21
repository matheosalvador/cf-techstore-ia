<?php

declare(strict_types=1);

namespace TechStore\Tests;

use PHPUnit\Framework\TestCase;
use TechStore\Database\Connection;
use TechStore\Repository\CategoryRepository;
use TechStore\Repository\OrderRepository;
use TechStore\Repository\ProductRepository;
use TechStore\Repository\UserRepository;

final class DatabaseTest extends TestCase
{
    private static \PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = new \PDO('sqlite::memory:');
        self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        self::$pdo->exec('PRAGMA foreign_keys = ON');
        self::$pdo->exec(file_get_contents(project_path('database/schema.sql')));
        self::$pdo->exec(file_get_contents(project_path('database/seed.sql')));
        Connection::reset(self::$pdo);
    }

    public function testSeedContainsExpectedCategories(): void
    {
        $categories = (new CategoryRepository(self::$pdo))->listAll();

        self::assertCount(3, $categories);
        self::assertContains('Ordinateurs', array_column($categories, 'name'));
    }

    public function testSeedContainsAtLeastTwelveProducts(): void
    {
        $products = (new ProductRepository(self::$pdo))->all();

        self::assertGreaterThanOrEqual(12, count($products));
        self::assertNotNull((new ProductRepository(self::$pdo))->find(12));
    }

    public function testSeedContainsDemoUsers(): void
    {
        $users = new UserRepository(self::$pdo);

        self::assertSame('customer', $users->findByEmail('alice.martin@example.test')['role']);
        self::assertSame('customer', $users->findByEmail('bob.dupont@example.test')['role']);
        self::assertSame('manager', $users->findByEmail('sophie.bernard@example.test')['role']);
    }

    public function testSeedContainsHistoricalOrders(): void
    {
        $orders = new OrderRepository(self::$pdo);
        $aliceOrders = $orders->findByUser(1);

        self::assertGreaterThanOrEqual(2, count($aliceOrders));
        self::assertSame('validated', $orders->findWithItems(1)['status']);
        self::assertNotEmpty($orders->findWithItems(1)['items']);
    }

    public function testProductSearchReturnsMatchingProduct(): void
    {
        $products = (new ProductRepository(self::$pdo))->search('Station', 'price_cents');

        self::assertSame('Station USB-C Essential', $products[0]['name']);
    }
}
