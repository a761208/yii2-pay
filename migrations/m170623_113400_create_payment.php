<?php

use yii\db\Migration;

class m170623_113400_create_payment extends Migration
{
    public function up()
    {
        $this->createTable('{{%payment}}', [
            'k'=>$this->string(128), // PK
            'v'=>$this->text(), // 值
        ]);
        try {
            $this->addPrimaryKey('PK_payment', '{{%payment}}', ['k']);
        } catch (\Exception $e) {
        }
    }

    public function down()
    {
        $this->dropTable('{{%payment}}');
    }
}
