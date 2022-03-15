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
 * Russian Roulette
 */
class Russianroulette extends Game
{
    /**
     * Game unique ID
     *
     * @var string
     */
    protected static $code = 'rr';

    /**
     * Game name / title
     *
     * @var string
     */
    protected static $title = 'الروليت الروسية';

    /**
     * Game description
     *
     * @var string
     */
    protected static $description = 'الروليت الروسي : هي لعبة حظ ، حيث يضع اللاعب جولة واحدة في مسدس ، ويدور الأسطوانة ، ويضع الكمامة على رأسه ، ويسحب الزناد.😬';

    /**
     * Game thumbnail image
     *
     * @var string
     */
    protected static $image = 'https://i.imgur.com/LffxQLK.jpg';

    /**
     * Order on the games list
     *
     * @var int
     */
    protected static $order = 30;

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
            return $this->answerCallbackQuery(__("You're not in this game!"), true);
        }

        $data = &$this->data['game_data'];

        $this->defineSymbols();

        $callbackquery_data = $this->manager->getUpdate()->getCallbackQuery()->getData();
        $callbackquery_data = explode(';', $callbackquery_data);

        $command = $callbackquery_data[1];

        $arg = $callbackquery_data[2] ?? null;

        if ($command === 'start') {
            if (isset($data['settings']) && $data['settings']['X'] == 'host') {
                $data['settings']['X'] = 'guest';
                $data['settings']['O'] = 'host';
            } else {
                $data['settings']['X'] = 'host';
                $data['settings']['O'] = 'guest';
            }

            $data['current_turn'] = 'X';
            $data['cylinder'] = ['', '', '', '', '', ''];
            /** @noinspection RandomApiMigrationInspection */
            $data['cylinder'][mt_rand(0, 5)] = 'X';

            Utilities::debugPrint('تهيئة اللعبة');
        } elseif ($arg === null) {
            Utilities::debugPrint('لم يتم استلام بيانات النقل');
        }

        if (isset($data['current_turn']) && $data['current_turn'] == 'E') {
            return $this->answerCallbackQuery(__("هذه اللعبة قد انتهت!"), true);
        }

        if ($this->getCurrentUserId() !== $this->getUserId($data['settings'][$data['current_turn']]) && $command !== 'start') {
            return $this->answerCallbackQuery(__("ليس دورك!"), true);
        }

        $hit = '';
        $gameOutput = '';

        if (isset($arg)) {
            if ($arg === 'null') {
                return $this->answerCallbackQuery();
            }

            if (!isset($data['cylinder'][$arg - 1])) {
                Utilities::debugPrint('تم تلقي بيانات نقل غير صالحة: ' . $arg);

                return $this->answerCallbackQuery(__("خطوة غير صحيحة!"), true);
            }

            Utilities::debugPrint('الغرفة المختارة: ' . $arg);

            if ($data['cylinder'][$arg - 1] === 'X') {
                Utilities::debugPrint('الغرفة تحتوي على رصاصة ، واللاعب ميت');

                if ($data['current_turn'] == 'X') {
                    $gameOutput = Emoji::skull() . ' <b>' . __("مات {PLAYER}! (مطرود)", ['{PLAYER}' => '</b>' . $this->getUserMention($data['settings']['X']) . '<b>']) . '</b>' . PHP_EOL;
                    $gameOutput .= Emoji::trophy() . ' <b>' . __("مات {PLAYER}! (مطرود)' => '</b>' . $this->getUserMention($data['settings']['O']) . '<b>']) . '</b>' . PHP_EOL;


                    if ($data['settings']['X'] === 'host') {
                        $this->data['players']['host'] = $this->data['players']['guest'];
                        $this->data['players']['guest'] = null;
                    } else {
                        $this->data['players']['guest'] = null;
                    }

                    $data['current_turn'] = 'E';
                } elseif ($data['current_turn'] == 'O') {
                    $gameOutput = Emoji::skull() . ' <b>' . __("{PLAYER} died! (kicked)", ['{PLAYER}' => '</b>' . $this->getUserMention($data['settings']['O']) . '<b>']) . '</b>' . PHP_EOL;
                    $gameOutput .= Emoji::trophy() . ' <b>' . __("{PLAYER} won!", ['{PLAYER}' => '</b>' . $this->getUserMention($data['settings']['X']) . '<b>']) . '</b>';


                    if ($data['settings']['X'] === 'host') {
                        $this->data['players']['host'] = $this->data['players']['guest'];
                        $this->data['players']['guest'] = null;
                    } else {
                        $this->data['players']['guest'] = null;
                    }

                    $data['current_turn'] = 'E';
                }

                $hit = $arg;

                if ($this->saveData($this->data)) {
                    return $this->editMessage($gameOutput . PHP_EOL . PHP_EOL . __('{PLAYER_HOST} ينتظر انضمام الخصم ...', ['{PLAYER_HOST}' => $this->getUserMention('host')]) . PHP_EOL . __('اضغط على الزر {BUTTON} للانضمام.', ['{BUTTON}' => '<b>\'' . __('أنظم') . '\'</b>']), $this->customGameKeyboard($hit));
                }
            }

            $gameOutput = Emoji::smilingFaceWithSunglasses() . ' <b>' . __("{PLAYER} survived!", ['{PLAYER}' => '</b>' . $this->getCurrentUserMention() . '<b>']) . '</b>' . PHP_EOL;

            if ($data['current_turn'] == 'X') {
                $data['current_turn'] = 'O';
            } elseif ($data['current_turn'] == 'O') {
                $data['current_turn'] = 'X';
            }

            $data['cylinder'] = ['', '', '', '', '', ''];
            /** @noinspection RandomApiMigrationInspection */
            $data['cylinder'][mt_rand(0, 5)] = 'X';
        }

        $gameOutput .= Emoji::playButton() . ' ' . $this->getUserMention($data['settings'][$data['current_turn']]);

        Utilities::isDebugPrintEnabled() && Utilities::debugPrint('Cylinder: |' . implode('|', $data['cylinder']) . '|');

        if ($this->saveData($this->data)) {
            return $this->editMessage(
                $this->getUserMention('host') . ' vs. ' . $this->getUserMention('guest') . PHP_EOL . PHP_EOL . $gameOutput,
                $this->customGameKeyboard($hit)
            );
        }

        return parent::gameAction();
    }

    /**
     * Define game symbols (emojis)
     */
    protected function defineSymbols(): void
    {
        $this->symbols['empty'] = '.';

        $this->symbols['chamber'] = Emoji::radioButton();
        $this->symbols['chamber_hit'] = Emoji::redCircle();
    }

    /**
     * Keyboard for game in progress
     *
     * @param string $hit
     *
     * @return InlineKeyboard
     * @throws BotException
     */
    protected function customGameKeyboard(string $hit = null): InlineKeyboard
    {
        $inline_keyboard[] = [
            new InlineKeyboardButton(
                [
                    'text'          => $this->symbols['empty'],
                    'callback_data' => self::getCode() . ';game;null',
                ]
            ),
            new InlineKeyboardButton(
                [
                    'text'          => ($hit == 1) ? $this->symbols['chamber_hit'] : $this->symbols['chamber'],
                    'callback_data' => self::getCode() . ';game;1',
                ]
            ),
            new InlineKeyboardButton(
                [
                    'text'          => ($hit == 2) ? $this->symbols['chamber_hit'] : $this->symbols['chamber'],
                    'callback_data' => self::getCode() . ';game;2',
                ]
            ),
            new InlineKeyboardButton(
                [
                    'text'          => $this->symbols['empty'],
                    'callback_data' => self::getCode() . ';game;null',
                ]
            ),
        ];

        $inline_keyboard[] = [
            new InlineKeyboardButton(
                [
                    'text'          => ($hit == 6) ? $this->symbols['chamber_hit'] : $this->symbols['chamber'],
                    'callback_data' => self::getCode() . ';game;6',
                ]
            ),
            new InlineKeyboardButton(
                [
                    'text'          => $this->symbols['empty'],
                    'callback_data' => self::getCode() . ';game;null',
                ]
            ),
            new InlineKeyboardButton(
                [
                    'text'          => ($hit == 3) ? $this->symbols['chamber_hit'] : $this->symbols['chamber'],
                    'callback_data' => self::getCode() . ';game;3',
                ]
            ),
        ];

        $inline_keyboard[] = [
            new InlineKeyboardButton(
                [
                    'text'          => $this->symbols['empty'],
                    'callback_data' => self::getCode() . ';game;null',
                ]
            ),
            new InlineKeyboardButton(
                [
                    'text'          => ($hit == 5) ? $this->symbols['chamber_hit'] : $this->symbols['chamber'],
                    'callback_data' => self::getCode() . ';game;5',
                ]
            ),
            new InlineKeyboardButton(
                [
                    'text'          => ($hit == 4) ? $this->symbols['chamber_hit'] : $this->symbols['chamber'],
                    'callback_data' => self::getCode() . ';game;4',
                ]
            ),
            new InlineKeyboardButton(
                [
                    'text'          => $this->symbols['empty'],
                    'callback_data' => self::getCode() . ';game;null',
                ]
            ),
        ];

        if (!is_numeric($hit)) {
            $inline_keyboard[] = [
                new InlineKeyboardButton(
                    [
                        'text'          => __('مغادرة'),
                        'callback_data' => self::getCode() . ';quit',
                    ]
                ),
                new InlineKeyboardButton(
                    [
                        'text'          => __('طرد'),
                        'callback_data' => self::getCode() . ';kick',
                    ]
                ),
            ];
        } else {
            $inline_keyboard[] = [
                new InlineKeyboardButton(
                    [
                        'text'          => __('مغادرة'),
                        'callback_data' => self::getCode() . ';quit',
                    ]
                ),
                new InlineKeyboardButton(
                    [
                        'text'          => __('أنظم'),
                        'callback_data' => self::getCode() . ';join',
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

        return new InlineKeyboard(...$inline_keyboard);
    }
}
