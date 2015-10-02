<?php
/**
 * @package Abricos
 * @subpackage Comment
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'models.php';

/**
 * Class CommentApp
 *
 * @property CommentManager $manager
 */
class CommentApp extends AbricosApplication {

    protected function GetClasses(){
        return array(
            'Statistic' => 'CommentStatistic',
            'StatisticList' => 'CommentStatisticList'
        );
    }

    protected function GetStructures(){
        return 'Statistic';
    }

    public function ResponseToJSON($d){
        return null;
    }

    /**
     * @param string $module Owner Module
     * @param string $type Owner Ids Type (Field Name)
     * @param int|array[int] $ownerids Owner Ids
     */
    public function StatisticList($module, $type, $ownerids){
        $models = $this->models;

        /** @var CommentStatisticList $list */
        $list = $models->InstanceClass('StatisticList');

        $rows = CommentQuery::StatisticList($this->db, $module, $type, $ownerids);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add($models->InstanceClass('Statistic', $d));
        }
        return $list;
    }

}

?>