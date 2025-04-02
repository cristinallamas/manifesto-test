<?php

/**
 * Class Game
 *
 * A light-hearted command-line text adventure.
 * Wander through rooms, make curious decisions,
 * combine weird stuff in your pockets, and maybe... escape with treasure?
 *
 * Features:
 * - Hearts system (don't die)
 * - Inventory with automatic item combination
 * - Branching story paths and silly flavour text
 * - Manual save and resume
 */
class Game
{
    private int $hearts;
    private bool $alive;
    private string $currentRoom;
    private string $saveFile = 'savegame.json';
    private array $inventory = [];

    /** Combinable items. Collect all required ones to fuse into something better. */
    private array $combinations = [
        ['items' => ['gem', 'key'], 'result' => 'amulet']
    ];

    /** The world map: each room has its vibe, question, and branching options. */
    private array $rooms = [
        'room1' => [
            'flavour' => "You are in a dungeon. A goblin stares at you menacingly.",
            'question' => "The Goblin charges toward you, blade drawn. Do you:",
            'choices' => [
                [
                    'text' => "Attack the goblin",
                    'outcome' => "You parry the goblin's strike and cleave it in two, but not before it nicks you.",
                    'heartsLost' => 1,
                    'next' => 'room2',
                    'addItem' => 'key'
                ],
                [
                    'text' => "Run away",
                    'outcome' => "You sprint toward the nearest exit, outpacing the goblin easily. He huffs behind you.",
                    'heartsLost' => 0,
                    'next' => 'room2'
                ]
            ]
        ],
        'room2' => [
            'flavour' => "You find a sparkling gem resting suspiciously on a pedestal.",
            'question' => "Do you take the gem? It glows like it’s begging to be pocketed.",
            'choices' => [
                [
                    'text' => "Yes, take the gem",
                    'outcome' => "You take the gem and feel a strange hum. Your hair stands on end.",
                    'heartsLost' => 0,
                    'next' => 'room3',
                    'addItem' => 'gem'
                ],
                [
                    'text' => "No, leave it. Too glowy.",
                    'outcome' => "You shake your head at cursed objects and walk past it like a sane person.",
                    'heartsLost' => 0,
                    'next' => 'room3'
                ]
            ]
        ],
        'room3' => [
            'flavour' => "You see a table with food and drink — probably not poisoned. Probably.",
            'question' => "You’re starving. Do you:",
            'choices' => [
                [
                    'text' => "Eat, drink grog, and nap a bit",
                    'outcome' => "You recover some strength and dream about 3-headed monkeys.",
                    'heartsLost' => -1,
                    'next' => 'room4'
                ],
                [
                    'text' => "Ignore the table. Trust no snacks.",
                    'outcome' => "Your pride gets the best of you. You faint into a patch of deadly hemlock.",
                    'heartsLost' => 3,
                    'next' => 'end'
                ]
            ]
        ],
        'room4' => [
            'flavour' => "You reach a glowing door. There’s a slot shaped suspiciously like an amulet.",
            'question' => "What do you do at the mysterious door?",
            'choices' => [
                [
                    'text' => "Use amulet to open the door",
                    'outcome' => "With a satisfying *click*, the door swings open to reveal treasure beyond your dreams.",
                    'heartsLost' => 0,
                    'next' => 'special_room',
                    'requiresItem' => 'amulet'
                ],
                [
                    'text' => "Go back and search for the gem",
                    'outcome' => "You sigh and trudge back to where the glowing rock was. It’s probably still there.",
                    'heartsLost' => 0,
                    'next' => 'room2'
                ],
                [
                    'text' => "Ignore the door and keep walking",
                    'outcome' => "You keep it low-key. Not all mysterious glowing doors need opening.",
                    'heartsLost' => 0,
                    'next' => 'room5'
                ]
            ]
        ],
        'special_room' => [
            'flavour' => "You step into a treasure chamber. Gold. Jewels. Bubblewrap. It’s glorious.",
            'question' => "Take the loot and make your getaway?",
            'choices' => [
                [
                    'text' => "Absolutely yes",
                    'outcome' => "You stuff your bag, strike a pose, and vanish through a golden portal.",
                    'heartsLost' => 0,
                    'next' => 'end'
                ]
            ]
        ],
        'room5' => [
            'flavour' => "You reach a library. An orangutan librarian watches you closely.",
            'question' => "The librarian says 'OOOK?' What do you do:",
            'choices' => [
                [
                    'text' => "Return the book you borrowed in 2007",
                    'outcome' => "The librarian accepts it silently. You are fined. Understandably.",
                    'heartsLost' => 1,
                    'next' => 'room6'
                ],
                [
                    'text' => "Borrow their top recommendation",
                    'outcome' => "You smile and nod. The librarian hands you a novel about time-traveling spoons.",
                    'heartsLost' => 0,
                    'next' => 'room6'
                ]
            ]
        ],
        'room6' => [
            'flavour' => "You find yourself on a strangely familiar beach. A rubber chicken with a pulley in the middle lies on the sand.",
            'question' => "A ghost pirate waves from a nearby docked ship. Do you:",
            'choices' => [
                [
                    'text' => "Pick up the rubber chicken. Obviously.",
                    'outcome' => "You pocket the chicken. Somehow, it feels like this will be useful… maybe not here.",
                    'heartsLost' => 0,
                    'next' => 'end',
                    'addItem' => 'rubber_chicken'
                ],
                [
                    'text' => "Wave back awkwardly and walk away",
                    'outcome' => "The ghost pirate tips his hat. You move along, whistling.",
                    'heartsLost' => 0,
                    'next' => 'end'
                ]
            ]
        ]
    ];

    /**
     * Game constructor. Loads a saved game or starts fresh.
     */
    public function __construct()
    {
        if (file_exists($this->saveFile)) {
            $choice = strtolower(trim(readline("A saved game was found. Do you want to continue? (yes/no): ")));
            if ($choice === 'yes') {
                $this->loadGame();
                return;
            }
        }

        $this->hearts = 3;
        $this->alive = true;
        $this->currentRoom = 'room1';
        $this->inventory = [];
    }

    /**
     * Main game loop.
     */
    public function play()
    {
        echo "\nWelcome to the Dungeon Adventure!\n";
        while ($this->alive && $this->currentRoom !== 'end') {
            $this->checkCombinations();
            $this->playRoom($this->rooms[$this->currentRoom]);
            if ($this->hearts <= 0) {
                $this->alive = false;
                echo "\nYou have lost all your hearts. Game over!\n";
                $this->deleteSave();
            } else {
                $this->saveGame();
            }
        }

        if ($this->alive && $this->currentRoom === 'end') {
            echo "\nCongratulations! You made it out alive with {$this->hearts} heart(s) left.\n";
            $this->deleteSave();
        }
    }

    /**
     * Checks and applies any valid item combinations.
     */
    private function checkCombinations()
    {
        foreach ($this->combinations as $combo) {
            if (count(array_intersect($combo['items'], $this->inventory)) === count($combo['items'])) {
                $this->inventory = array_diff($this->inventory, $combo['items']);
                $this->inventory[] = $combo['result'];
                echo "\n(You combined " . implode(" + ", $combo['items']) . " to make: {$combo['result']})\n";
            }
        }
    }

    /**
     * Runs the logic for the current room.
     */
    private function playRoom(array $room)
    {
        echo "\n{$room['flavour']}\n";
        echo "{$room['question']}\n";

        $validChoices = [];
        foreach ($room['choices'] as $i => $choice) {
            if (isset($choice['requiresItem']) && !in_array($choice['requiresItem'], $this->inventory)) {
                continue;
            }
            $validChoices[] = $choice;
            echo "  " . count($validChoices) . ". {$choice['text']}\n";
        }

        echo "  s. Save and exit\n";
        echo "  i. Show inventory\n";

        $valid = false;
        $selected = null;

        while (!$valid) {
            echo "> ";
            $input = trim(fgets(STDIN));

            if (strtolower($input) === 's') {
                $this->saveGame();
                echo "\nGame saved. You can resume it later.\n";
                exit();
            }

            if (strtolower($input) === 'i') {
                echo "\nYour Inventory:\n";
                echo empty($this->inventory) ? "(Empty)\n" : implode(", ", $this->inventory) . "\n";
                continue;
            }

            if (is_numeric($input)) {
                $index = (int)$input - 1;
                if (isset($validChoices[$index])) {
                    $selected = $validChoices[$index];
                    $valid = true;
                } else {
                    echo "Invalid choice. Please enter a valid number, or 's', or 'i'.\n";
                }
            } else {
                echo "Please enter a valid number, or 's', or 'i'.\n";
            }
        }

        echo $selected['outcome'] . "\n";

        if (isset($selected['addItem'])) {
            $this->inventory[] = $selected['addItem'];
            echo "(You received an item: {$selected['addItem']})\n";
        }

        if (isset($selected['removeItem'])) {
            $key = array_search($selected['removeItem'], $this->inventory);
            if ($key !== false) {
                unset($this->inventory[$key]);
                $this->inventory = array_values($this->inventory);
                echo "(You used the item: {$selected['removeItem']})\n";
            }
        }

        $change = $selected['heartsLost'];
        if ($change > 0) {
            $this->hearts -= $change;
            echo "(You lost {$change} heart(s). Hearts remaining: {$this->hearts})\n";
        } elseif ($change < 0) {
            $healed = min(abs($change), 3 - $this->hearts);
            $this->hearts += $healed;
            echo "(You gained {$healed} heart(s). Hearts now: {$this->hearts})\n";
        }

        $this->currentRoom = $selected['next'];
    }

    /**
     * Saves the game state to a file.
     */
    private function saveGame()
    {
        $data = [
            'hearts' => $this->hearts,
            'currentRoom' => $this->currentRoom,
            'inventory' => $this->inventory
        ];
        file_put_contents($this->saveFile, json_encode($data));
    }

    /**
     * Loads a previously saved game.
     */
    private function loadGame()
    {
        $data = json_decode(file_get_contents($this->saveFile), true);
        $this->hearts = $data['hearts'];
        $this->currentRoom = $data['currentRoom'];
        $this->inventory = $data['inventory'] ?? [];
        $this->alive = true;
    }

    /**
     * Deletes the saved game file.
     */
    private function deleteSave()
    {
        if (file_exists($this->saveFile)) {
            unlink($this->saveFile);
        }
    }
}

$game = new Game();
$game->play();