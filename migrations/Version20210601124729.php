<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210601124729 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE discount ADD discount_id BIGINT NOT NULL after product_id');
        $this->addSql('ALTER TABLE discount_history ADD discount_id BIGINT NOT NULL after product_id');
        $this->addSql('ALTER TABLE product ADD is_favorited TINYINT(1) NOT NULL after img_link');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE discount DROP discount_id');
        $this->addSql('ALTER TABLE discount_history DROP discount_id');
        $this->addSql('ALTER TABLE product DROP is_favorited');
    }
}
