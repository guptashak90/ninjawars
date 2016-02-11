<?php
namespace NinjaWars\core\data;

use NinjaWars\core\data\Message;
use NinjaWars\core\data\DatabaseConnection;
use \PDO;
use \Player;

/**
 * who/what/why/where
 *
 * Ninja clans with their various members
 */
class Clan {
    private $id;
    private $name;
    private $avatarUrl;
    private $description;
    private $founder;

    public function __construct($p_id=null, $p_name=null, $data=null) {
        $this->setID($p_id);

        if (!$p_name) {
            $p_name = $this->nameFromId($p_id);
        }

        $this->setName($p_name);

        if ($data) {
            $this->setAvatarUrl($data['clan_avatar_url']);
            $this->setDescription($data['description']);
            $this->setFounder($data['clan_founder']);
        }
    }

    public function getID() {
        return $this->id;
    }

    public function id() {
        return $this->getID();
    }

    public function getName() {
        return $this->name;
    }

    public function setID($p_id) {
        $this->id = (int)$p_id;
    }

    public function setName($p_name) {
        $this->name = trim($p_name);
    }

    /**
     * @return int
     */
    public function getLeaderID() {
        $leaderInfo = $this->getLeaderInfo();
        return $leaderInfo['player_id'];
    }

    /**
     * @return string
     */
    public function getFounder() {
        if (!$this->founder) {
            $this->founder = query_item('select clan_founder from clan where clan_id = :id', [':id'=>$this->getId()]);
        }

        return $this->founder;
    }

    public function setDescription($desc) {
        $this->description = (string) $desc;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setFounder($founder) {
        $this->founder = $founder;
    }

    /**
     * @return string
     */
    public function getAvatarUrl() {
        return $this->avatarUrl;
    }

    public function setAvatarUrl($url) {
        $this->avatarUrl = $url;
    }

    // End of getters and setters.

    private function nameFromId($id) {
        return query_item(
            'SELECT clan_name FROM clan WHERE clan_id = :id',
            [ ':id' => [$id, PDO::PARAM_INT]]
        );
    }

    public function getLeaderInfo() {
        return get_clan_leader_info($this->getID());
    }

    /**
     * @return boolean|String
     */
    public function addMember(Player $ninja, Player $adder) {
        if ($this->hasMember($ninja->id())) {
            return 'That ninja is already a member of the clan.';
        }

        // Not an insert_query because there is no sequence involved or needed.
        query('insert into clan_player (_clan_id, _player_id) values (:c, :p)', [':c'=>$this->id(), ':p'=>$ninja->id()]);
        query('update players set verification_number = :new_num where player_id = :id', [':new_num'=>rand(1, 999999), ':id'=>$ninja->id()]);

        Message::create([
            'send_from' => $adder->id(),
            'send_to'   => $ninja->id(),
            'message'   => "CLAN: You have been accepted into ".$this->getName(),
            'type'      => 0,
        ]);

        return true;
    }

    /**
     * Passively invite a character to a clan with a message and link.
     *
     * @return string
     */
    public function invite(Player $p_target, Player $p_inviter) {
        if (!$p_target || empty($p_target)) {
            return 'No such ninja.';
        }

        $active = $p_target->isActive();

        if (!$active) {
            $failureError = 'That ninja is not active.';
        } else {
            $inviteMsg = $p_inviter->name().' has invited you into their clan, '.$this->getName().'. '
                .'To accept, choose their clan '.$this->getName().' on the '
                .message_url('clan.php?command=view&clan_id='.$this->getID(), 'clan joining page').'.';

            Message::create([
                'send_from' => $p_inviter->id(),
                'send_to'   => $p_target->id(),
                'message'   => $inviteMsg,
                'type'      => 0,
            ]);

            $failureError = null;
        }

        return $failureError;
    }

    /**
     * For when a player chooses to leave their clan of their own volition.
     */
    public function leave(Player $ninja) {
        $this->kickMember($ninja->id(), $ninja, true);
    }

    /**
     * When a leader removes a member without choice.
     */
    public function kickMember($playerId, Player $kicker, $selfLeave=false) {
        $today = date("F j, Y, g:i a");

        query(
            "DELETE FROM clan_player WHERE _player_id = :player AND _clan_id = :clan",
            [
                ':player' => $playerId,
                ':clan'   => $this->getID(),
            ]
        );

        if ($selfLeave) {
            $msg = "You have been kicked out of ".$this->getName()." by ".$kicker->name()." on $today.";
        } else {
            $msg = "You have left clan ".$this->getName()." on $today.";
        }

        Message::create([
            'send_from' => $kicker->id(),
            'send_to'   => $playerId,
            'message'   => $msg,
            'type'      => 0,
        ]);

        return true;
    }

    public function hasMember($playerId) {
        $query = 'SELECT _player_id FROM clan_player WHERE _player_id = :pid AND _clan_id = :clan_id';
        $args  = [
            ':pid'     => $playerId,
            ':clan_id' => $this->id(),
        ];

        return (bool) query_item($query, $args);
    }

    /**
     * @return array(int, int, ...)
     */
    public function getMemberIds() {
        $playerRows = query_array(
            'SELECT player_id FROM players LEFT JOIN clan_player ON _player_id = player_id WHERE _clan_id = :cid',
            [':cid' => $this->id()]
        );

        $ids = array();

        foreach ($playerRows as $row) {
            $ids[] = $row['player_id'];
        }

        return $ids;
    }

    /**
     * Delete a clan after sending a message to all clan members.
     */
    public function disband() {
        DatabaseConnection::getInstance();
        $leader = $this->getLeaderID();

        $message = "Your leader has disbanded your clan. You are alone again.";

        $statement = DatabaseConnection::$pdo->prepare("SELECT _player_id FROM clan_player WHERE _clan_id = :clan");
        $statement->bindValue(':clan', $this->getID());
        $statement->execute();

        while ($data = $statement->fetch()) {
            $memberId = $data[0];

            Message::create([
                'send_from' => $leader,
                'send_to'   => $memberId,
                'message'   => $message,
                'type'      => 0,
            ]);
        }

        // Deletion of the clan_player connections should cascade from the deletion of the clan, at least ideally.
        $statement = DatabaseConnection::$pdo->prepare("DELETE FROM clan WHERE clan_id = :clan");
        $statement->bindValue(':clan', $this->getID());
        $statement->execute();
    }

    public function promoteMember($ninja_id) {
        $query = 'UPDATE clan_player SET member_level = (member_level + 1) WHERE _player_id = :pid';
        $args = [
            ':pid' => $ninja_id,
        ];

        return (bool) update_query($query, $args);
    }

    /**
     * Get the members of a clan,
     */
    public function getMembers() {
        $membersArray = query_array(
            'SELECT uname, accounts.active_email as email, clan_name, level, days, clan_founder, player_id, member_level '.
            'FROM clan JOIN clan_player ON _clan_id = :clan_id AND clan_id = _clan_id JOIN players ON player_id = clan_player._player_id '.
            'JOIN account_players on player_id = account_players._player_id join accounts on account_id = _account_id '.
            'AND active = 1 ORDER BY level, health DESC',
            [':clan_id' => $this->id()]
        );

        $max = query_item(
            'SELECT max(level) AS max '.
            'FROM clan '.
            'JOIN clan_player ON _clan_id = :clan_id AND clan_id = _clan_id '.
            'JOIN players ON player_id = _player_id AND active = 1',
            [':clan_id'=>$this->id()]
        );

        // Modify the members by reference
        foreach ($membersArray as &$member) {
            $member['leader'] = false;
            $member['size']   = floor( ( ($member['level'] - $member['days'] < 1 ? 0 : $member['level'] - $member['days']) / $max) * 2) + 1;

            // Calc the member display size based on their level relative to the max.
            if ($member['member_level'] >= 1) {
                $member['leader'] = true;
                $member['size']   = max($member['size'] + 2, 3);
            }

            $member['gravatar_url'] = generate_gravatar_url($member['player_id']);
        }

        return $membersArray;
    }
}