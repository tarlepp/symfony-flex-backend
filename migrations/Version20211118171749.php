<?php
declare(strict_types = 1);

// phpcs:ignoreFile
namespace DoctrineMigrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211118171749 extends AbstractMigration
{
    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function getDescription(): string
    {
        return 'Initial database structure';
    }

    /**
     * @throws Exception
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('CREATE TABLE api_key (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', token VARCHAR(40) NOT NULL COMMENT \'Generated API key string for authentication\', description LONGTEXT NOT NULL, created_by_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', updated_by_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C912ED9DB03A8386 (created_by_id), INDEX IDX_C912ED9D896DBBDE (updated_by_id), UNIQUE INDEX uq_token (token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_swedish_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE api_key_has_user_group (api_key_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', user_group_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', INDEX IDX_E2D0E7F98BE312B3 (api_key_id), INDEX IDX_E2D0E7F91ED93D47 (user_group_id), PRIMARY KEY(api_key_id, user_group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_swedish_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE date_dimension (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', year INT NOT NULL COMMENT \'A full numeric representation of a year, 4 digits\', month INT NOT NULL COMMENT \'Day of the month without leading zeros; 1 to 12\', day INT NOT NULL COMMENT \'Day of the month without leading zeros; 1 to 31\', quarter INT NOT NULL COMMENT \'Calendar quarter; 1, 2, 3 or 4\', week_number INT NOT NULL COMMENT \'ISO-8601 week number of year, weeks starting on Monday\', day_number_of_week INT NOT NULL COMMENT \'ISO-8601 numeric representation of the day of the week; 1 (for Monday) to 7 (for Sunday)\', day_number_of_year INT NOT NULL COMMENT \'The day of the year (starting from 0); 0 through 365\', leap_year TINYINT(1) NOT NULL COMMENT \'Whether it\'\'s a leap year or not\', week_numbering_year INT NOT NULL COMMENT \'ISO-8601 week-numbering year.\', unix_time BIGINT NOT NULL COMMENT \'Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)\', date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', INDEX date (date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_swedish_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE healthz (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', timestamp DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_swedish_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE log_login (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', user_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', client_type VARCHAR(255) DEFAULT NULL, client_name VARCHAR(255) DEFAULT NULL, client_short_name VARCHAR(255) DEFAULT NULL, client_version VARCHAR(255) DEFAULT NULL, client_engine VARCHAR(255) DEFAULT NULL, os_name VARCHAR(255) DEFAULT NULL, os_short_name VARCHAR(255) DEFAULT NULL, os_version VARCHAR(255) DEFAULT NULL, os_platform VARCHAR(255) DEFAULT NULL, device_name VARCHAR(255) DEFAULT NULL, brand_name VARCHAR(255) DEFAULT NULL, model VARCHAR(255) DEFAULT NULL, type ENUM(\'failure\', \'success\') NOT NULL COMMENT \'(DC2Type:EnumLogLogin)\', time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', `date` DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', agent LONGTEXT NOT NULL, http_host VARCHAR(255) NOT NULL, client_ip VARCHAR(255) NOT NULL, INDEX user_id (user_id), INDEX date (date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_swedish_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE log_login_failure (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', user_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', timestamp DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX user_id (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_swedish_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE log_request (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', user_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', api_key_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', status_code INT NOT NULL, response_content_length INT NOT NULL, is_main_request TINYINT(1) NOT NULL, time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', `date` DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', agent LONGTEXT NOT NULL, http_host VARCHAR(255) NOT NULL, client_ip VARCHAR(255) NOT NULL, headers LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', method VARCHAR(255) NOT NULL, scheme VARCHAR(5) NOT NULL, base_path VARCHAR(255) NOT NULL, script VARCHAR(255) NOT NULL, path VARCHAR(255) DEFAULT NULL, query_string LONGTEXT DEFAULT NULL, uri LONGTEXT NOT NULL, controller VARCHAR(255) DEFAULT NULL, content_type VARCHAR(255) DEFAULT NULL, content_type_short VARCHAR(255) DEFAULT NULL, is_xml_http_request TINYINT(1) NOT NULL, action VARCHAR(255) DEFAULT NULL, content LONGTEXT DEFAULT NULL, parameters LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', INDEX user_id (user_id), INDEX api_key_id (api_key_id), INDEX request_date (date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_swedish_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (role VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, created_by_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', updated_by_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_57698A6AB03A8386 (created_by_id), INDEX IDX_57698A6A896DBBDE (updated_by_id), UNIQUE INDEX uq_role (role), PRIMARY KEY(role)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_swedish_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', username VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, language ENUM(\'en\', \'fi\') NOT NULL COMMENT \'User language for translations(DC2Type:EnumLanguage)\', locale ENUM(\'en\', \'fi\') NOT NULL COMMENT \'User locale for number, time, date, etc. formatting.(DC2Type:EnumLocale)\', timezone VARCHAR(255) DEFAULT \'Europe/Helsinki\' NOT NULL COMMENT \'User timezone which should be used to display time, date, etc.\', password VARCHAR(255) NOT NULL COMMENT \'Hashed password\', created_by_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', updated_by_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\',  created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_8D93D649B03A8386 (created_by_id), INDEX IDX_8D93D649896DBBDE (updated_by_id), UNIQUE INDEX uq_username (username), UNIQUE INDEX uq_email (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_swedish_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_has_user_group (user_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', user_group_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', INDEX IDX_2C59957A76ED395 (user_id), INDEX IDX_2C599571ED93D47 (user_group_id), PRIMARY KEY(user_id, user_group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_swedish_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_group (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', role VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, created_by_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', updated_by_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_8F02BF9D57698A6A (role), INDEX IDX_8F02BF9DB03A8386 (created_by_id), INDEX IDX_8F02BF9D896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_swedish_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE api_key ADD CONSTRAINT FK_C912ED9DB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE api_key ADD CONSTRAINT FK_C912ED9D896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE api_key_has_user_group ADD CONSTRAINT FK_E2D0E7F98BE312B3 FOREIGN KEY (api_key_id) REFERENCES api_key (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE api_key_has_user_group ADD CONSTRAINT FK_E2D0E7F91ED93D47 FOREIGN KEY (user_group_id) REFERENCES user_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE log_login ADD CONSTRAINT FK_8A76204DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_login_failure ADD CONSTRAINT FK_EDB4AF3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE log_request ADD CONSTRAINT FK_35AB708A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_request ADD CONSTRAINT FK_35AB7088BE312B3 FOREIGN KEY (api_key_id) REFERENCES api_key (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE role ADD CONSTRAINT FK_57698A6AB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE role ADD CONSTRAINT FK_57698A6A896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user_has_user_group ADD CONSTRAINT FK_2C59957A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_has_user_group ADD CONSTRAINT FK_2C599571ED93D47 FOREIGN KEY (user_group_id) REFERENCES user_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_group ADD CONSTRAINT FK_8F02BF9D57698A6A FOREIGN KEY (role) REFERENCES role (role) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_group ADD CONSTRAINT FK_8F02BF9DB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user_group ADD CONSTRAINT FK_8F02BF9D896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * @throws Exception
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('ALTER TABLE api_key_has_user_group DROP FOREIGN KEY FK_E2D0E7F98BE312B3');
        $this->addSql('ALTER TABLE log_request DROP FOREIGN KEY FK_35AB7088BE312B3');
        $this->addSql('ALTER TABLE user_group DROP FOREIGN KEY FK_8F02BF9D57698A6A');
        $this->addSql('ALTER TABLE api_key DROP FOREIGN KEY FK_C912ED9DB03A8386');
        $this->addSql('ALTER TABLE api_key DROP FOREIGN KEY FK_C912ED9D896DBBDE');
        $this->addSql('ALTER TABLE log_login DROP FOREIGN KEY FK_8A76204DA76ED395');
        $this->addSql('ALTER TABLE log_login_failure DROP FOREIGN KEY FK_EDB4AF3A76ED395');
        $this->addSql('ALTER TABLE log_request DROP FOREIGN KEY FK_35AB708A76ED395');
        $this->addSql('ALTER TABLE role DROP FOREIGN KEY FK_57698A6AB03A8386');
        $this->addSql('ALTER TABLE role DROP FOREIGN KEY FK_57698A6A896DBBDE');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649B03A8386');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649896DBBDE');
        $this->addSql('ALTER TABLE user_has_user_group DROP FOREIGN KEY FK_2C59957A76ED395');
        $this->addSql('ALTER TABLE user_group DROP FOREIGN KEY FK_8F02BF9DB03A8386');
        $this->addSql('ALTER TABLE user_group DROP FOREIGN KEY FK_8F02BF9D896DBBDE');
        $this->addSql('ALTER TABLE api_key_has_user_group DROP FOREIGN KEY FK_E2D0E7F91ED93D47');
        $this->addSql('ALTER TABLE user_has_user_group DROP FOREIGN KEY FK_2C599571ED93D47');
        $this->addSql('DROP TABLE api_key');
        $this->addSql('DROP TABLE api_key_has_user_group');
        $this->addSql('DROP TABLE date_dimension');
        $this->addSql('DROP TABLE healthz');
        $this->addSql('DROP TABLE log_login');
        $this->addSql('DROP TABLE log_login_failure');
        $this->addSql('DROP TABLE log_request');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_has_user_group');
        $this->addSql('DROP TABLE user_group');
    }
}
