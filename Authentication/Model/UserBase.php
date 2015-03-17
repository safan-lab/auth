<?php
namespace Authentication\Model;

use GapOrm\Mapper\FieldMapper;
use GapOrm\Mapper\Model;

class UserBase extends Model
{
    /**
     * Constructor
     */
    public function __construct(){
        $field = new FieldMapper($this->table(), 'id', parent::FIELD_TYPE_INT);
        $field->pk(true);
        $field->noinsert(true);
        $field->noupdate(true);
        $this->addField($field);
        $field = new FieldMapper($this->table(), 'email', parent::FIELD_TYPE_STR);
        $this->addField($field);
        $field = new FieldMapper($this->table(), 'username', parent::FIELD_TYPE_STR);
        $this->addField($field);
        $field = new FieldMapper($this->table(), 'password', parent::FIELD_TYPE_STR);
        $this->addField($field);
        $field = new FieldMapper($this->table(), 'hash', parent::FIELD_TYPE_STR);
        $this->addField($field);
        $field = new FieldMapper($this->table(), 'hashCreationDate', parent::FIELD_TYPE_DATETIME);
        $this->addField($field);
        $field = new FieldMapper($this->table(), 'creationDate', parent::FIELD_TYPE_DATETIME);
        $this->addField($field);
        $field = new FieldMapper($this->table(), 'lastLoginDate', parent::FIELD_TYPE_DATETIME);
        $this->addField($field);
    }

    /**
     * @param string $className
     * @return mixed
     */
    public static function instance($className=__CLASS__)
    {
        return parent::instance($className);
    }

    /**
     * @return string
     */
    public function table()
    {
        return 'users';
    }
}