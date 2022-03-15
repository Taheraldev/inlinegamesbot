<?php
/**
 * Inline Games - Telegram Bot (@inlinegamesbot)
 *
 * (c) 2016-2021 Jack'lul <jacklulcat@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bot\Entity\Game;

use Bot\Entity\Game;
use Bot\Exception\BotException;
use Bot\Exception\StorageException;
use Bot\Helper\Utilities;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Spatie\Emoji\Emoji;

/**
 * Rock-Paper-Scissors
 */
class Rockpaperscissors extends Game
{
    /**
     * Game unique ID
     *
     * @var string
     */
    protected static $code = 'rps';

    /**
     * Game name / title
     *
     * @var string
     */
    protected static $title = 'قرعة 📝';

    /**
     * Game description
     *
     * @var string
     */
    protected static $description = 'لعبة القرعة 📝: هي لعبة يقوم فيها كل لاعب في نفس الوقت بتشكيل واحد من ثلاثة أشكال بيد ممدودة.🧮';

    /**
     * Game thumbnail image
     *
     * @var string
     */
    protected static $image = 'https://i.imgur.com/1H8HI7n.png';

    /**
     * Order on the games list
     *
     * @var int
     */
    protected static $order = 20;

    /**
     * Game handler
     *
     * @return ServerResponse
     *
     * @throws BotException
     * @throws TelegramException
     * @throws StorageException
     */
    protected function gameAction(): ServerResponse
    {
        if ($this->getCurrentUserId() !== $this->getUserId('host') && $this->getCurrentUserId() !== $this->getUserId('guest')) {
            return $this->answerCallbackQuery(__("أنت لست في هذه اللعبة!"), true);
        }

        $data = &$this->data['game_data'];

        $this->defineSymbols();

        $callbackquery_data = $this->manager->getUpdate()->getCallbackQuery()->getData();
        $callbackquery_data = explode(';', $callbackquery_data);

        $command = $callbackquery_data[1];

        $arg = $callbackquery_data[2] ?? null;

        if ($command === 'start') {
            $data['host_pick'] = '';
            $data['guest_pick'] = '';
            $data['host_wins'] = 0;
            $data['guest_wins'] = 0;
            $data['round'] = 1;
            $data['current_turn'] = '';

            Utilities::debugPrint('تهيئة اللعبة');
        } elseif ($arg === null) {
            Utilities::debugPrint('لم يتم استلام بيانات النقل');
        }

        if (isset($data['current_turn']) && $data['current_turn'] == 'E') {
            return $this->answerCallbackQuery(__("هذه اللعبة قد انتهت!"), true);
        }

        Utilities::debugPrint('Argument: ' . $arg);

        if (isset($arg)) {
            if (in_array($arg, $this->symbols['valid'])) {
                if ($this->getCurrentUserId() === $this->getUserId('host') && $data['host_pick'] == '') {
                    $data['host_pick'] = $arg;
                } elseif ($this->getCurrentUserId() === $this->getUserId('guest') && $data['guest_pick'] == '') {
                    $data['guest_pick'] = $arg;
                }

                if ($this->saveData($this->data)) {
                    Utilities::isDebugPrintEnabled() && Utilities::debugPrint($this->getCurrentUserMention() . ' picked ' . $arg);
                }
            } else {
                Utilities::debugPrint('نقل البيانات غير صالح: ' . $arg);

                return $this->answerCallbackQuery(__("خطوة غير صحيحة!"), true);
            }
        }

        $isOver = false;
        $gameOutput = '';
        $hostPick = '';
        $guestPick = '';

        if ($data['host_pick'] != '' && $data['guest_pick'] != '') {
            $isOver = $this->isGameOver($data['host_pick'], $data['guest_pick']);

            if (in_array($isOver, ['X', 'O', 'T'])) {
                $data['round'] += 1;

                if ($isOver == 'X') {
                    $data['host_wins'] = $data['host_wins'] + 1;

                    $gameOutput = Emoji::sportsMedal() . ' <b>' . __("{PLAYER} فاز في هذه الجولة!", ['{PLAYER}' => '</b>' . $this->getUserMention('host') . '<b>']) . '</b>' . PHP_EOL;
                } elseif ($isOver == 'O') {
                    $data['guest_wins'] = $data['guest_wins'] + 1;

                    $gameOutput = Emoji::sportsMedal() . ' <b>' . __("{PLAYER} فاز في هذه الجولة!", ['{PLAYER}' => '</b>' . $this->getUserMention('guest') . '<b>']) . '</b>' . PHP_EOL;
                } else {
                    $gameOutput = Emoji::chequeredFlag() . ' <b>' . __("انتهت هذه الجولة بالتعادل!") . '</b>' . PHP_EOL;
                }
            }

            $hostPick = ' (' . $this->symbols[$data['host_pick'] . '_short'] . ')';
            $guestPick = ' (' . $this->symbols[$data['guest_pick'] . '_short'] . ')';
        }

        if (($data['host_wins'] >= 3 && $data['host_wins'] > $data['guest_wins']) || $data['host_wins'] >= $data['guest_wins'] + 3 || ($data['round'] > 5 && $data['host_wins'] > $data['guest_wins'])) {
            $gameOutput = Emoji::trophy() . ' <b>' . __("فاز {PLAYER} باللعبة!", ['{PLAYER}' => '</b>' . $this->getUserMention('host') . '<b>']) . '</b>';

            $data['current_turn'] = 'E';
        } elseif (($data['guest_wins'] >= 3 && $data['guest_wins'] > $data['host_wins']) || $data['guest_wins'] >= $data['host_wins'] + 3 || ($data['round'] > 5 && $data['guest_wins'] > $data['host_wins'])) {
            $gameOutput = Emoji::trophy() . ' <b>' . __("فاز {PLAYER} باللعبة!", ['{PLAYER}' => '</b>' . $this->getUserMention('guest') . '<b>']) . '</b>';

            $data['current_turn'] = 'E';
        } else {
            $gameOutput .= '<b>' . __("Round {ROUND} - make your picks!", ['{ROUND}' => $data['round']]) . '</b>';

            if ($data['host_pick'] != '' && $data['guest_pick'] === '') {
                $gameOutput .= PHP_EOL . '<b>' . __("انتظر ل:") . '</b> ' . $this->getUserMention('guest');
            } elseif ($data['guest_pick'] != '' && $data['host_pick'] === '') {
                $gameOutput .= PHP_EOL . '<b>' . __("انتظر ل:") . '</b> ' . $this->getUserMention('host');
            } else {
                $data['host_pick'] = '';
                $data['guest_pick'] = '';
            }

            $isOver = false;
        }

        if ($this->saveData($this->data)) {
            return $this->editMessage(
                $this->getUserMention('host') . (($data['host_wins'] > 0 || $data['guest_wins'] > 0) ? ' (' . $data['host_wins'] . ')' : '') . $hostPick . ' vs. ' . $this->getUserMention('guest') . (($data['guest_wins'] > 0 || $data['host_wins'] > 0) ? ' (' . $data['guest_wins'] . ')' : '') . $guestPick . PHP_EOL . PHP_EOL . $gameOutput,
                $this->customGameKeyboard($isOver)
            );
        }

        return parent::gameAction();
    }

    /**
     * Define game symbols (emojis)
     */
    protected function defineSymbols(): void
    {
        $this->symbols['R'] = 'ROCK';
        $this->symbols['R_short'] = Emoji::raisedFist();
        $this->symbols['P'] = 'PAPER';
        $this->symbols['P_short'] = Emoji::raisedHand();
        $this->symbols['S'] = 'SCISSORS';
        $this->symbols['S_short'] = Emoji::victoryHand();
        $this->symbols['valid'] = ['R', 'P', 'S'];
    }

    /**
     * Check whenever game is over
     *
     * @param string $x
     * @param string $y
     *
     * @return string
     */
    protected function isGameOver(string $x, string $y): ?string
    {
        if ($x == 'P' && $y == 'R') {
            return 'X';
        }

        if ($y == 'P' && $x == 'R') {
            return 'O';
        }

        if ($x == 'R' && $y == 'S') {
            return 'X';
        }

        if ($y == 'R' && $x == 'S') {
            return 'O';
        }

        if ($x == 'S' && $y == 'P') {
            return 'X';
        }

        if ($y == 'S' && $x == 'P') {
            return 'O';
        }

        if ($y == $x) {
            return 'T';
        }

        return null;
    }

    /**
     * Keyboard for game in progress
     *
     * @param bool $isOver
     *
     * @return InlineKeyboard
     * @throws BotException
     */
    protected function customGameKeyboard(bool $isOver = false): InlineKeyboard
    {
        if (!$isOver) {
            $inline_keyboard[] = [
                new InlineKeyboardButton(
                    [
                        'text'          => $this->symbols['R'] . ' ' . $this->symbols['R_short'],
                        'callback_data' => self::getCode() . ';game;R',
                    ]
                ),
                new InlineKeyboardButton(
                    [
                        'text'          => $this->symbols['P'] . ' ' . $this->symbols['P_short'],
                        'callback_data' => self::getCode() . ';game;P',
                    ]
                ),
                new InlineKeyboardButton(
                    [
                        'text'          => $this->symbols['S'] . ' ' . $this->symbols['S_short'],
                        'callback_data' => self::getCode() . ';game;S',
                    ]
                ),
            ];
        } else {
            $inline_keyboard[] = [
                new InlineKeyboardButton(
                    [
                        'text'          => __('العب مرة أخرى!'),
                        'callback_data' => self::getCode() . ';start',
                    ]
                ),
            ];
        }

        if (getenv('DEBUG') && $this->getCurrentUserId() == getenv('BOT_ADMIN')) {
            $inline_keyboard[] = [
                new InlineKeyboardButton(
                    [
                        'text'          => 'DEBUG: ' . 'اعادة تشغيل',
                        'callback_data' => self::getCode() . ';start',
                    ]
                ),
            ];
        }

        $inline_keyboard[] = [
            new InlineKeyboardButton(
                [
                    'text'          => __('مغادرة'),
                    'callback_data' => self::getCode() . ';quit',
                ]
            ),
            new InlineKeyboardButton(
                [
                    'text'          => __('ركل'),
                    'callback_data' => self::getCode() . ';kick',
                ]
            ),
        ];

        return new InlineKeyboard(...$inline_keyboard);
    }
}
