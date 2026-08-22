<?php
class MAdmin extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }
    public function insert($data, $table)
    {
        $this->db->insert($table, $data);
        return $this->db->insert_id();
        //   echo $this->db->last_query();
    }
    function update($where, $data, $table)
    {
        $this->db->where($where);
        return  $this->db->update($table, $data);
    }
    public function delete($where, $table)
    {
        return $this->db->delete($table, $where);
    }
    public function update_set($where, $var, $val, $tt, $table)
    {
        $this->db->set($var, $var . $tt . $val, FALSE);
        $this->db->where($where);
        return $this->db->update($table);
        //   echo $this->db->last_query();
    }
    public function num_rows($where, $table)
    {
        $this->db->select('*');
        $this->db->where($where);
        return $this->db->get($table)->num_rows();
    }
    public function num_rows_or($where = '', $or = [], $table)
    {
        $this->db->select('*');
        if ($where != '') {
            $this->db->where($where);
        }
        if ($or != null) {
            $this->db->group_start();
            $this->db->or_where($or);
            $this->db->group_end();
        }
        return $this->db->get($table)->num_rows();
        // echo $this->db->last_query();
    }
    public function num_rows_candi($where = '', $or = [], $table)
    {
        $this->db->select('candidates.*, category.name as name_cat');
        $this->db->join('category', 'category.id = candidates.cate');
        if ($where != '') {
            $this->db->where($where);
        }
        if ($or != null) {
            $this->db->group_start();
            $this->db->or_where($or);
            $this->db->group_end();
        }
        return $this->db->get($table)->num_rows();
    }
    public function get_by($where, $table)
    {
        $this->db->select('*');
        if ($where != '') {
            $this->db->where($where);
        }
        $this->db->order_by('id', 'DESC');
        return $this->db->get($table)->row_array();
        // echo $this->db->last_query();
    }
    public function check_where_or($where, $or = [], $table)
    {
        $this->db->select('*');
        if ($where != '') {
            $this->db->where($where);
        }
        if ($or != null) {
            $this->db->group_start();
            $this->db->or_where($or);
            $this->db->group_end();
        }
        return $this->db->get($table)->row_array();
    }
    public function list_where_or($where, $or = [], $table)
    {
        $this->db->select('*');
        if ($where != '') {
            $this->db->where($where);
        }
        if ($or != null) {
            $this->db->group_start();
            $this->db->or_where($or);
            $this->db->group_end();
        }
        $this->db->order_by('updated_at', 'DESC');
        return $this->db->get($table)->result_array();
    }
    public function list_uv_or($where, $or = [], $table)
    {
        $this->db->select('candidates.*, category.name as name_cat');
        $this->db->join('category', 'category.id = candidates.cate');
        if ($where != '') {
            $this->db->where($where);
        }
        if ($or != null) {
            $this->db->group_start();
            $this->db->or_where($or);
            $this->db->group_end();
        }
        $this->db->order_by('updated_at', 'DESC');
        return $this->db->get($table)->result_array();
    }
    public function get_list($where, $table)
    {
        $this->db->select('*');
        if ($where != '') {
            $this->db->where($where);
        }
        return $this->db->get($table)->result_array();
    }
    public function get_list_desc($where, $table)
    {
        $this->db->select('*');
        if ($where != '') {
            $this->db->where($where);
        }
        $this->db->order_by('id', 'DESC');
        return $this->db->get($table)->result_array();
    }
    public function query_sql($sql)
    {
        $query = $this->db->query($sql);
        return $query->result_array();
    }
    public function query_sql_row($sql)
    {
        $query = $this->db->query($sql);
        return $query->row_array();
    }
    public function query_sql_num($sql)
    {
        $query = $this->db->query($sql);
        return $query->num_rows();
    }
    function get_limit($where, $table, $start = '', $limit = '')
    {
        $this->db->select('*');
        if ($where != '') {
            $this->db->where($where);
        }
        if ($limit >= 1) {
            $this->db->limit($limit, $start);
        }
        $this->db->order_by('id', 'DESC');
        return $this->db->get($table)->result_array();
        // echo $this->db->last_query();
    }
    function get_limit_or($where, $or = [], $table, $start = '', $limit = '')
    {
        $this->db->select('*');
        if ($where != '') {
            $this->db->where($where);
        }
        if ($or != null) {
            $this->db->group_start();
            $this->db->or_where($or);
            $this->db->group_end();
        }
        if ($limit >= 1) {
            $this->db->limit($limit, $start);
        }
        $this->db->order_by('id', 'DESC');
        return $this->db->get($table)->result_array();
        // echo $this->db->last_query();
    }
    public function getAllRunePathsWithSlotsAndRunes()
    {
        $this->db->select([
            'runes_paths.id as runes_path_id',
            'runes_paths.key as rune_path_key',
            'runes_paths.icon as rune_path_icon',
            'runes_paths.name as rune_path_name',
            'slots.id as slot_id',
            'slots.slot_index as slot_index',
            'runes.id as rune_id',
            'runes.key as rune_key',
            'runes.icon as rune_icon',
            'runes.name as rune_name',
            'runes.shortDesc as rune_shortDesc',
            'runes.longDesc as rune_longDesc',
        ]);
        $this->db->from('runes_paths');
        $this->db->join('slots', 'slots.runes_path_id = runes_paths.id');
        $this->db->join('runes', 'runes.slot_id = slots.id');
        $query = $this->db->get();
        $result = [];
        foreach ($query->result_array() as $row) {
            $runePathId = $row['runes_path_id'];
            $slotId = $row['slot_id'];
            if (!isset($result[$runePathId])) {
                $result[$runePathId] = [
                    'rune_path' => [
                        'id' => $row['runes_path_id'],
                        'key' => $row['rune_path_key'],
                        'icon' => $row['rune_path_icon'],
                        'name' => $row['rune_path_name'],
                    ],
                    'slots' => []
                ];
            }
            if (!isset($result[$runePathId]['slots'][$slotId])) {
                $result[$runePathId]['slots'][$slotId] = [
                    'slot' => [
                        'slot_index' => $row['slot_index']
                    ],
                    'runes' => []
                ];
            }
            $result[$runePathId]['slots'][$slotId]['runes'][] = [
                'id' => $row['rune_id'],
                'key' => $row['rune_key'],
                'icon' => $row['rune_icon'],
                'name' => $row['rune_name'],
                'shortDesc' => $row['rune_shortDesc'],
                'longDesc' => $row['rune_longDesc'],
            ];
        }
        return array_values($result);
    }
    public function getRunesByRunePathId($runePathId)
    {
        $this->db->select([
            'runes_paths.id as runes_path_id',
            'runes_paths.key as rune_path_key',
            'runes_paths.icon as rune_path_icon',
            'runes_paths.name as rune_path_name',
            'slots.id as slot_id',
            'slots.slot_index as slot_index',
            'runes.id as rune_id',
            'runes.key as rune_key',
            'runes.icon as rune_icon',
            'runes.name as rune_name',
            'runes.shortDesc as rune_shortDesc',
            'runes.longDesc as rune_longDesc',
        ]);
        $this->db->from('runes_paths');
        $this->db->join('slots', 'slots.runes_path_id = runes_paths.id');
        $this->db->join('runes', 'runes.slot_id = slots.id');
        $this->db->where('runes_paths.id', $runePathId);
        $query = $this->db->get();
        if ($query->num_rows() == 0) {
            return null;
        }
        $result = null;
        foreach ($query->result_array() as $row) {
            if ($result === null) {
                $result = [
                    'id' => $row['runes_path_id'],
                    'key' => $row['rune_path_key'],
                    'icon' => $row['rune_path_icon'],
                    'name' => $row['rune_path_name'],
                    'slots' => []
                ];
            }
            $slotId = $row['slot_id'];
            if (!isset($result['slots'][$slotId])) {
                $result['slots'][$slotId] = [
                    'slot_index' => $row['slot_index'],
                    'runes' => []
                ];
            }
            $result['slots'][$slotId]['runes'][] = [
                'id' => $row['rune_id'],
                'key' => $row['rune_key'],
                'icon' => $row['rune_icon'],
                'name' => $row['rune_name'],
                'shortDesc' => $row['rune_shortDesc'],
                'longDesc' => $row['rune_longDesc'],
            ];
        }
        usort($result['slots'], function ($a, $b) {
            return $a['slot_index'] <=> $b['slot_index'];
        });
        return $result;
    }

    public function parseItems($itemsJson)
    {
        $items = $itemsJson['items'];
        $parsedItems = [];
        foreach ($items as $item) {
            if ($item['item_id']) {
                $itemDetail = $this->db->where('id', $item['item_id'])->get('items')->row();
                $parsedItems[] = [
                    'item_id' => $item['item_id'],
                    'item_detail' => $itemDetail
                ];
            }
        }

        return [
            'win_rate' => $itemsJson['win_rate'],
            'match_number' => $itemsJson['match_number'],
            'items' => $parsedItems
        ];
    }

    public function parseLaterItems($itemsJson)
    {
        $items = $itemsJson['items'];
        $parsedItems = [];
        foreach ($items as $item) {
            if ($item['item_id']) {
                $itemDetail = $this->db->where('id', $item['item_id'])->get('items')->row();
                $parsedItems[] = [
                    'item_id' => $item['item_id'],
                    'win_rate' => $item['win_rate'],
                    'item_detail' => $itemDetail
                ];
            }
        }

        return $parsedItems;
    }

    public function parseBestItems($itemsJson)
    {
        $parsedItems = [];
        foreach ($itemsJson as $key => $items) {
            foreach ($items as $item) {
                if ($item['item_id']) {
                    $itemDetail = $this->db->where('id', $item['item_id'])->get('items')->row();
                    $parsedItems[$key][] = [
                        'item_id' => $item['item_id'],
                        'win_rate' => $item['win_rate'],
                        'item_detail' => $itemDetail
                    ];
                }
            }
        }
        return $parsedItems;
    }

    public function parseSpells($spellsJson)
    {
        $spells = $spellsJson;

        $parsedSpells = [];
        foreach ($spells['spell_id'] as $spellId) {
            $spellDetail = $this->db->where('id', $spellId)->get('champion_spells')->row();
            $parsedSpells[] = [
                'spell_id' => $spellId,
                'spell_detail' => $spellDetail
            ];
        }

        return [
            'spell_id' => $spells['id'],
            'spell_detail' => $parsedSpells,
            'win_rate' => $spells['win_rate'],
            'match_number' => $spells['match_number']
        ];
    }

    public function parseChampionSpells($championSpellsJson)
    {
        $championSpells = $championSpellsJson;

        $parsedChampionSpells = [];
        foreach ($championSpells as $spellId => $spellData) {
            $spellDetail = $this->db->where('spell_id', $spellData['spell_id'])->get('champion_spells')->row();
            $parsedChampionSpells[] = [
                'spell_id' => $spellData['spell_id'],
                'spell_detail' => $spellDetail,
                'upgrade' => $spellData['upgrades']
            ];
        }

        return $parsedChampionSpells;
    }

    public function parseSummonerSpells($summonerSpellsJson)
    {
        $summonerSpells = $summonerSpellsJson;

        $parsedSummonerSpells = [];
        foreach ($summonerSpells['summoner_id'] as $summonerId) {
            $summonerSpellDetail = $this->db->where('id', $summonerId)->get('summoner_spells')->row();
            if ($summonerSpellDetail) {
                $parsedSummonerSpells[] = [
                    'summoner_id' => $summonerId,
                    'summoner_spell_detail' => $summonerSpellDetail,
                    'win_rate' => $summonerSpells['win_rate'],
                    'match_number' => $summonerSpells['match_number']
                ];
            }
        }

        return $parsedSummonerSpells;
    }

    public function parseStrongChampions($strongChampionsJson)
    {
        $strongChampions = $strongChampionsJson;

        $parsedStrongChampions = [];
        foreach ($strongChampions as $championData) {
            if (isset($championData['champion_id'])) {
                $championDetail = $this->db->where('id', $championData['champion_id'])->get('champions')->row();
                $parsedStrongChampions[] = [
                    'champion_id' => $championData['champion_id'],
                    'champion_detail' => $championDetail,
                    'win_rate' => $championData['win_rate'],
                    'match_number' => $championData['match_number']
                ];
            }
        }

        return $parsedStrongChampions;
    }

    public function parseWeakChampions($weakChampionsJson)
    {
        $weakChampions = $weakChampionsJson;

        $parsedWeakChampions = [];
        foreach ($weakChampions as $championData) {
            if (isset($championData['champion_id'])) {
                $championDetail = $this->db->where('id', $championData['champion_id'])->get('champions')->row();
                $parsedWeakChampions[] = [
                    'champion_id' => $championData['champion_id'],
                    'champion_detail' => $championDetail,
                    'win_rate' => $championData['win_rate'],
                    'match_number' => $championData['match_number']
                ];
            }
        }

        return $parsedWeakChampions;
    }

    public function parseRunePath($json)
    {
        $runePathDetail = $this->parseRunesByRunePathId($json['runnerPathId'], $json['selectedRunes']);
        return [
            'runnerPathId' => $json['runnerPathId'],
            'runePathDetail' => $runePathDetail,
            'selectedRunes' => $json['selectedRunes'],
        ];
    }
    public function parseRunesByRunePathId($runePathId, $selectedRunes)
    {
        // Retrieve the rune path along with related slots and runes
        $runePath = $this->db->select('runes_paths.*, slots.slot_index, runes.*')
            ->from('runes_paths')
            ->join('slots', 'slots.runes_path_id = runes_paths.id')
            ->join('runes', 'runes.slot_id = slots.id')
            ->where('runes_paths.id', $runePathId)
            ->get()
            ->result_array();

        if (!$runePath) {
            return null;
        }

        // Map selected runes by their slot index
        $selectedRunesMap = [];
        foreach ($selectedRunes as $selectedRune) {
            if (isset($selectedRune['runeId'])) {
                $selectedRunesMap[$selectedRune['slotIndex']] = $selectedRune['runeId'];
            }
        }

        log_message('info', 'selectedRunesMap: ' . json_encode($selectedRunesMap));

        // Structure the rune path data
        $runesData = [
            'id' => $runePath[0]['id'],
            'key' => $runePath[0]['key'],
            'icon' => $runePath[0]['icon'],
            'name' => $runePath[0]['name'],
            'slots' => []
        ];

        // Organize slots and runes based on slot_index
        $slots = [];
        foreach ($runePath as $rune) {
            $slotIndex = $rune['slot_index'];
            if (!isset($slots[$slotIndex])) {
                $slots[$slotIndex] = [
                    'slot_index' => $slotIndex,
                    'runes' => []
                ];
            }

            $isSelected = isset($selectedRunesMap[$slotIndex]) && $rune['id'] == $selectedRunesMap[$slotIndex];

            $slots[$slotIndex]['runes'][] = [
                'id' => $rune['id'],
                'key' => $rune['key'],
                'icon' => $rune['icon'],
                'name' => $rune['name'],
                'shortDesc' => $rune['shortDesc'],
                'longDesc' => $rune['longDesc'],
                'isSelected' => $isSelected
            ];
        }

        // Reorganize runes and handle the special cases based on the logic provided
        foreach ($slots as &$slot) {
            $newRunes = [];
            $isLast = false;

            foreach ($slot['runes'] as $index => $rune) {
                if ($index == 3 && $rune['isSelected']) {
                    $isLast = true;
                }
                $newRunes[] = $rune;
            }

            if (isset($newRunes[2]) && $isLast) {
                unset($newRunes[2]);
            } else {
                $newRunes = array_slice($newRunes, 0, 3);
            }

            $slot['runes'] = $newRunes;
        }

        // Sort slots by their index and add to runesData
        usort($slots, function ($a, $b) {
            return $a['slot_index'] - $b['slot_index'];
        });

        $runesData['slots'] = array_values($slots);

        log_message('info', 'runesData: ' . json_encode($runesData));

        return $runesData;
    }
}
