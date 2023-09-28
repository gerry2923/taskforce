<?php
namespace taskforce\models; 
use yii\db\ActiveRecord;


class Contact extends ActiveRecord
{

  public function attributeLabels()
  {
      return [
          'name' => 'Имя',
          'phone' => 'Телефон',
          'email' => 'Электронная почта',
          'position' => 'Должность'
      ];
  }  


}
