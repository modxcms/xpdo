<?php

/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Test\Migrations;

use Symfony\Component\Console\Tester\CommandTester;
use xPDO\Console\Application;
use xPDO\xPDO;

class MigrationConsoleTest extends MigrationTestCase
{
    /** @var string */
    private $cliConfigFile;

    /** @var string */
    private $cliSqlitePath;

    /**
     * @before
     */
    public function setUpCliConfig()
    {
        $this->cliSqlitePath = sys_get_temp_dir() . '/xpdo_cli_mig_' . uniqid('', true) . '.sqlite';
        $driver = self::$properties['xpdo_driver'];
        if ($driver !== 'sqlite') {
            $this->markTestSkipped('CLI migration integration tests run on sqlite only');
        }

        $options = self::$properties['sqlite_array_options'];
        $options[xPDO::OPT_CONNECTIONS] = [
            [
                'dsn' => 'sqlite:' . $this->cliSqlitePath,
                'username' => '',
                'password' => '',
                'options' => [
                    xPDO::OPT_CONN_MUTABLE => true,
                ],
                'driverOptions' => self::$properties['sqlite_array_driverOptions'],
            ],
        ];

        $config = [
            'xpdo_driver' => 'sqlite',
            'sqlite_array_options' => $options,
            'migrations_path' => $this->migrationsDir,
            'migrations_namespace' => $this->migrationsNamespace,
            'migrations_table' => $this->migrationsTable,
        ];

        $this->cliConfigFile = sys_get_temp_dir() . '/xpdo_cli_cfg_' . uniqid('', true) . '.php';
        $export = var_export($config, true);
        file_put_contents($this->cliConfigFile, "<?php\nuse xPDO\\xPDO;\nreturn {$export};\n");
    }

    /**
     * @after
     */
    public function tearDownCliConfig()
    {
        if ($this->cliConfigFile && is_file($this->cliConfigFile)) {
            @unlink($this->cliConfigFile);
        }
        if ($this->cliSqlitePath && is_file($this->cliSqlitePath)) {
            @unlink($this->cliSqlitePath);
        }
    }

    public function testMigrateCreateStatusMigrateRollbackViaCli()
    {
        $create = $this->tester('migrate-create');
        $exit = $create->execute([
            'description' => 'CreateCliTable',
            '--config' => $this->cliConfigFile,
            '--platform' => 'sqlite',
        ]);
        $this->assertSame(0, $exit, $create->getDisplay());
        $display = $create->getDisplay();
        $this->assertStringContainsString('Created migration:', $display);
        $this->assertMatchesRegularExpression('/[0-9]{14}_CreateCliTable/', $display);

        $files = glob($this->migrationsDir . '/*_CreateCliTable.php');
        $this->assertCount(1, $files);

        // Replace stub with a real up/down against a side table name encoded in the migration.
        $name = basename($files[0], '.php');
        $side = $this->sideTable();
        $this->writeMigration(
            $name,
            "\$xpdo = \$context->getXpdo();\n        \$t = \$xpdo->escape('{$side}');\n        \$xpdo->{'exec'}(\"CREATE TABLE {\$t} (id INTEGER PRIMARY KEY, note TEXT NOT NULL)\");",
            "\$xpdo = \$context->getXpdo();\n        \$t = \$xpdo->escape('{$side}');\n        \$xpdo->{'exec'}(\"DROP TABLE IF EXISTS {\$t}\");"
        );

        $statusMissing = $this->tester('migrate-status');
        $exit = $statusMissing->execute([
            '--config' => $this->cliConfigFile,
            '--platform' => 'sqlite',
        ]);
        $this->assertSame(0, $exit, $statusMissing->getDisplay());
        $this->assertStringContainsString('Repository: missing', $statusMissing->getDisplay());

        $migrate = $this->tester('migrate');
        $exit = $migrate->execute([
            '--config' => $this->cliConfigFile,
            '--platform' => 'sqlite',
        ]);
        $this->assertSame(0, $exit, $migrate->getDisplay());
        $this->assertStringContainsString('Applied batch', $migrate->getDisplay());

        $statusOk = $this->tester('migrate-status');
        $exit = $statusOk->execute([
            '--config' => $this->cliConfigFile,
            '--platform' => 'sqlite',
        ]);
        $this->assertSame(0, $exit, $statusOk->getDisplay());
        $out = $statusOk->getDisplay();
        $this->assertStringContainsString('Repository: ok', $out);
        $this->assertStringContainsString($name, $out);
        $this->assertStringContainsString('Pending:', $out);

        $rollback = $this->tester('migrate-rollback');
        $exit = $rollback->execute([
            '--config' => $this->cliConfigFile,
            '--platform' => 'sqlite',
        ]);
        $this->assertSame(0, $exit, $rollback->getDisplay());
        $this->assertStringContainsString('Rolled back batch', $rollback->getDisplay());
    }

    public function testMigrateCreateHelpDefinitionAndInvalidDescription()
    {
        $application = new Application();
        $application->loadCommands();
        $command = $application->find('migrate-create');
        $this->assertSame('migrate-create', $command->getName());
        $this->assertNotEmpty($command->getDescription());
        $this->assertTrue($command->getDefinition()->hasArgument('description'));
        $this->assertTrue($command->getDefinition()->hasOption('config'));

        $bad = $this->tester('migrate-create');
        $exit = $bad->execute([
            'description' => '9Bad',
            '--config' => $this->cliConfigFile,
            '--platform' => 'sqlite',
        ]);
        $this->assertSame(1, $exit);
        $this->assertStringContainsString('fatal:', $bad->getDisplay());
        $this->assertStringNotContainsString('#0 ', $bad->getDisplay());
    }

    public function testMigrateRejectsInvalidStep()
    {
        $migrate = $this->tester('migrate');
        $exit = $migrate->execute([
            '--config' => $this->cliConfigFile,
            '--platform' => 'sqlite',
            '--step' => '0',
        ]);
        $this->assertSame(1, $exit);
        $this->assertStringContainsString('fatal:', $migrate->getDisplay());
        $this->assertStringContainsString('--step', $migrate->getDisplay());
    }

    public function testMigrateFailsWithoutMigrationsPath()
    {
        $driver = self::$properties['xpdo_driver'];
        if ($driver !== 'sqlite') {
            $this->markTestSkipped('CLI negative-path test runs on sqlite only');
        }
        $options = self::$properties['sqlite_array_options'];
        $options[xPDO::OPT_CONNECTIONS] = [
            [
                'dsn' => 'sqlite:' . $this->cliSqlitePath,
                'username' => '',
                'password' => '',
                'options' => [xPDO::OPT_CONN_MUTABLE => true],
                'driverOptions' => self::$properties['sqlite_array_driverOptions'],
            ],
        ];
        $config = [
            'xpdo_driver' => 'sqlite',
            'sqlite_array_options' => $options,
            // migrations_path intentionally omitted
            'migrations_namespace' => $this->migrationsNamespace,
            'migrations_table' => $this->migrationsTable,
        ];
        $badConfig = sys_get_temp_dir() . '/xpdo_cli_bad_' . uniqid('', true) . '.php';
        $export = var_export($config, true);
        file_put_contents($badConfig, "<?php\nuse xPDO\\xPDO;\nreturn {$export};\n");
        try {
            $status = $this->tester('migrate-status');
            $exit = $status->execute([
                '--config' => $badConfig,
                '--platform' => 'sqlite',
            ]);
            $this->assertSame(1, $exit);
            $this->assertStringContainsString('fatal:', $status->getDisplay());
            $this->assertStringContainsString('migrations_path', $status->getDisplay());
        } finally {
            @unlink($badConfig);
        }
    }

    private function tester(string $commandName): CommandTester
    {
        $application = new Application();
        $application->setAutoExit(false);
        $application->loadCommands();
        return new CommandTester($application->find($commandName));
    }
}
