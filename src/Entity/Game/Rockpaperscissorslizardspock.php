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

use Bot\Exception\BotException;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Spatie\Emoji\Emoji;

/**
 * Rock-Paper-Scissors-Lizard-Spock
 */
class Rockpaperscissorslizardspock extends Rockpaperscissors
{
    /**
     * Game unique ID
     *
     * @var string
     */
    protected static $code = 'rpsls';

    /**
     * Game name / title
     *
     * @var string
     */
    protected static $title = 'حجر، ورقة، مقص، سحلية، رجل الفضاء';

    /**
     * Game description
     *
     * @var string
     */
    protected static $description = 'حجر، ورقة، مقص، سحلية، رجل الفضاء👊🏻: هي لعبة يقوم فيها كل لاعب بتشكيل واحد من خمسة أشكال في نفس الوقت بيد ممدودة.';

    /**
     * Game thumbnail image
     *
     * @var string
     */
    protected static $image = 'https://i.imgur.com/vSMnT88.png';

    /**
     * Order on the games list
     *
     * @var int
     */
    protected static $order = 21;

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
        $this->symbols['L'] = 'LIZARD';
        $this->symbols['L_short'] = Emoji::okHand();
        $this->symbols['A'] = 'SPOCK';
        $this->symbols['A_short'] = Emoji::vulcanSalute();
        $this->symbols['valid'] = ['R', 'P', 'S', 'L', 'A'];
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

        if ($x == 'P' && $y == 'A') {
            return 'X';
        }

        if ($y == 'P' && $x == 'A') {
            return 'O';
        }

        if ($x == 'R' && $y == 'S') {
            return 'X';
        }

        if ($y == 'R' && $x == 'S') {
            return 'O';
        }

        if ($x == 'R' && $y == 'L') {
            return 'X';
        }

        if ($y == 'R' && $x == 'L') {
            return 'O';
        }

        if ($x == 'S' && $y == 'P') {
            return 'X';
        }

        if ($y == 'S' && $x == 'P') {
            return 'O';
        }

        if ($x == 'S' && $y == 'L') {
            return 'X';
        }

        if ($y == 'S' && $x == 'L') {
            return 'O';
        }

        if ($x == 'L' && $y == 'P') {
            return 'X';
        }

        if ($y == 'L' && $x == 'P') {
            return 'O';
        }

        if ($x == 'L' && $y == 'A') {
            return 'X';
        }

        if ($y == 'L' && $x == 'A') {
            return 'O';
        }

        if ($x == 'A' && $y == 'S') {
            return 'X';
        }

        if ($y == 'A' && $x == 'S') {
            return 'O';
        }

        if ($x == 'A' && $y == 'R') {
            return 'X';
        }

        if ($y == 'A' && $x == 'R') {
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
            $inline_keyboard[] = [
                new InlineKeyboardButton(
                    [
                        'text'          => $this->symbols['L'] . ' ' . $this->symbols['L_short'],
                        'callback_data' => self::getCode() . ';game;L',
                    ]
                ),
                new InlineKeyboardButton(
                    [
                        'text'          => $this->symbols['A'] . ' ' . $this->symbols['A_short'],
                        'callback_data' => self::getCode() . ';game;A',
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
