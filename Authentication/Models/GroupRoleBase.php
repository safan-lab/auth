<?php
/**
 * This file is part of the Safan package.
 *
 * (c) Harut Grigoryan <ceo@safanlab.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Authentication\Models;

use GapOrm\Mapper\FieldMapper;
use GapOrm\Mapper\Model;

class GroupRoleBase extends Model
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
        $field = new FieldMapper($this->table(), 'groupID', parent::FIELD_TYPE_INT);
        $this->addField($field);
        $field = new FieldMapper($this->table(), 'roleID', parent::FIELD_TYPE_INT);
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
        return 'groupRoles';
    }
}