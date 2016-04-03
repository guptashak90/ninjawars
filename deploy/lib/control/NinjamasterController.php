<?php
namespace NinjaWars\core\control;

use Symfony\Component\HttpFoundation\RedirectResponse;
use NinjaWars\core\data\NpcFactory;
use NinjaWars\core\data\AdminViews;
use NinjaWars\core\data\Player;
use NinjaWars\core\data\Account;

/**
 * The ninjamaster/admin info
 */
class NinjamasterController {
    const ALIVE = false;
    const PRIV  = false;
    protected $charId = null;
    protected $self = null;

    public function __construct() {
        $this->charId = self_char_id();
        $this->self = Player::find($this->charId);
    }

    /**
     * If the player isn't logged in, or isn't admin, return a redirect
     *
     * @return RedirectResponse|boolean
     */
    private function requireAdmin($player) {
        if ($player === null || !$player instanceof Player || !$player->isAdmin()) {
            // Redirect to the root site.
            return new RedirectResponse(WEB_ROOT);
        } else {
            return true;
        }
    }

    /**
     * Display the main admin area
     *
     * Includes player viewing, account duplicates checking, npc balacing
     *
     * @return ViewSpec|RedirectResponse
     */
    public function index() {
        $result = $this->requireAdmin($this->self);

        if ($result instanceof RedirectResponse) {
            return $result;
        }

        $charInfos        = null;
        $charInventory    = null;
        $firstMessage     = null;
        $firstChar        = null;
        $firstAccount     = null;
        $firstDescription = null;
        $dupes            = AdminViews::duped_ips();
        $stats            = AdminViews::high_rollers();
        $npcs             = NpcFactory::allNonTrivialNpcs();
        $trivialNpcs      = NpcFactory::allTrivialNpcs();

        $char_ids  = preg_split("/[,\s]+/", in('view'));
        $char_name = trim(in('char_name'));

        if ($char_name) { // View a target non-self character
            $firstChar = Player::findByName($char_name);
            $char_ids  = [$firstChar->id()];
        }

        if (!empty($char_ids)) {
            $firstChar        = ($firstChar ? $firstChar : Player::find(reset($char_ids)));
            $firstAccount     = Account::findByChar($firstChar);
            $charInfos        = AdminViews::split_char_infos($char_ids);
            $charInventory    = AdminViews::char_inventory($char_ids);
            $firstMessage     = $firstChar->messages;
            $firstDescription = $firstChar->description;
        }

        $parts = [
            'stats'             => $stats,
            'first_char'        => $firstChar,
            'first_description' => $firstDescription,
            'first_message'     => $firstMessage,
            'first_account'     => $firstAccount,
            'char_infos'        => $charInfos,
            'dupes'             => $dupes,
            'char_inventory'    => $charInventory,
            'char_name'         => $char_name,
            'npcs'              => $npcs,
            'trivial_npcs'      => $trivialNpcs,
        ];

        return [
            'title'    => 'Admin Actions',
            'template' => 'ninjamaster.tpl',
            'parts'    => $parts,
            'options'  => null,
        ];
    }

    /**
     * Display the tools page
     *
     * @return ViewSpec|RedirectResponse
     */
    public function tools() {
        $result = $this->requireAdmin($this->self);

        if ($result instanceof RedirectResponse) {
            return $result;
        }

        return [
            'title'    => 'Admin Tools',
            'template' => 'page.tools.tpl',
            'parts'    => [],
            'options'  => [ 'private' => false ],
        ];
    }

    /**
     * Display a list of characters ranked by score/difficulty.
     *
     * @return ViewSpec|RedirectResponse
     */
    public function player_tags() {
        $result = $this->requireAdmin($this->self);

        if ($result instanceof RedirectResponse) {
            return $result;
        }

        return [
            'title'    => 'Player Character Tags',
            'template' => 'player-tags.tpl',
            'parts'    => [ 'player_size' => $this->playerSize() ],
            'options'  => [ 'quickstat' => false ],
        ];
    }

    /**
     * @return Array
     */
    private function playerSize() {
        $res = [];
        DatabaseConnection::getInstance();
        $sel = "SELECT (level-3-round(days/5)) AS sum, player_id, uname FROM players WHERE active = 1 AND health > 0 ORDER BY sum DESC";
        $statement = DatabaseConnection::$pdo->query($sel);

        $player_info = $statement->fetch();

        $max = $player_info['sum'];

        do {
            // make percentage of highest, multiply by 10 and round to give a 1-10 size
            $res[$player_info['uname']] = [
                'player_id' => $player_info['player_id'],
                'size'      => $this->calculatePlayerSize($player_info['sum'], $max),
            ];
        } while ($player_info = $statement->fetch());

        return $res;
    }

    /**
     * @param int $p_rank
     * @param int $p_max
     * @return int
     */
    private function calculatePlayerSize($p_rank, $p_max) {
        return floor(( (($p_rank-1 < 1 ? 0 : $p_rank-1)) / $p_max)*10)+1;
    }
}
