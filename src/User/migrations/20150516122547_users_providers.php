<?php

use Phinx\Migration\AbstractMigration;

class UsersProviders extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     *
     * Uncomment this method if you would like to use it.
     *
    public function change()
    {
    }
    */
    
    /**
     * Migrate Up.
     */
    public function up()
    {
        $this->table('users_providers')
            ->addColumn('provider', 'string', ['limit' => 255])
            ->addColumn('identifier', 'string', ['limit' => 255])
            ->addColumn('user', 'integer')
            ->addColumn('created', 'timestamp', ['timezone' => false])
            ->addColumn('changed', 'timestamp', ['timezone' => false])
            ->addColumn("data", "jsonb", ["null" => true])
            ->addForeignKey('user', 'users', 'id', array('delete' => 'RESTRICT', 'update' => 'RESTRICT'))
            ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {

    }
}