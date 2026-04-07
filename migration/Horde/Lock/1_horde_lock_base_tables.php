<?php

class HordeLockBaseTables extends Horde_Db_Migration_Base
{
    public function up()
    {
        if (!in_array('horde_locks', $this->tables())) {
            $t = $this->createTable('horde_locks', ['autoincrementKey' => ['lock_id']]);
            $t->column('lock_id', 'string', ['limit' => 36, 'null' => false]);
            $t->column('lock_owner', 'string', ['limit' => 32, 'null' => false]);
            $t->column('lock_scope', 'string', ['limit' => 32, 'null' => false]);
            $t->column('lock_principal', 'string', ['limit' => 255, 'null' => false]);
            $t->column('lock_origin_timestamp', 'bigint', ['null' => false]);
            $t->column('lock_update_timestamp', 'bigint', ['null' => false]);
            $t->column('lock_expiry_timestamp', 'bigint', ['null' => false]);
            $t->column('lock_type', 'smallint', ['null' => false, 'unsigned' => true]);
            $t->end();
        }
    }

    public function down()
    {
        $this->dropTable('horde_locks');
    }
}
