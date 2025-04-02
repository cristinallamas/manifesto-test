<?php

class Game
{
    private int $hearts = 3;
    private int $roomIndex = 0;
    private bool $alive = true;

    /**
     * 
     * Each room should has:
     * Some flavour text
     * A question
     * A set of answers for the player to choose from
     */
    private array $rooms = [
        [
            'flavour' => "You are in a dungeon. A goblin stares at you menacingly.",
            'question' => "The Goblin charges toward you, blade drawn. Do you:",
            'choices' => [
                [
                    'text' => "Attack the goblin",
                    'outcome' => "You parry the goblin's strike and cleave it in two, but not before it nicks you.",
                    'heartsLost' => 1
                ],
                [
                    'text' => "Run away",
                    'outcome' => "You sprint toward the nearest exit, outpacing the goblin easily.",
                    'heartsLost' => 0
                ]
            ]
        ],
        [
            'flavour' => "You run down a corridor…",
            'question' => "At the end of the corridor, you find two doors. Do you:",
            'choices' => [
                [
                    'text' => "Go through the right-hand door",
                    'outcome' => "You fall down a 3 meter drop and injure your ankle.",
                    'heartsLost' => 1
                ],
                [
                    'text' => "Go through the left-hand door",
                    'outcome' => "The door locks behind you and you are in an open courtyard.",
                    'heartsLost' => 0
                ]
            ]
        ],
        [
            'flavour' => "You see a table with food and drink.",
            'question' => "You are tired, hungry and thirsty. Do you:",
            'choices' => [
                [
                    'text' => "Eat, drink and rest",
                    'outcome' => "You recover from your injuries.",
                    'heartsLost' => -1
                ],
                [
                    'text' => "Ignore the table, fearing poison",
                    'outcome' => "You fall into a bed of hemlock. You die. Horribly. Ouch!",
                    'heartsLost' => 3
                ]
            ]
        ],
        [
            'flavour' => "You are now in a beer cellar. The barkeep offers you a beer.",
            'question' => "Do you:",
            'choices' => [
                [
                    'text' => "Accept the offer",
                    'outcome' => "You get horribly drunk and stagger off.",
                    'heartsLost' => 1
                ],
                [
                    'text' => "Decline and ask for directions to the W.C.",
                    'outcome' => "You reach the W.C. and have a wash.",
                    'heartsLost' => 0
                ]
            ]
        ],
        [
            'flavour' => "You reach a library. The librarian, an orangutan, looks at you.",
            'question' => "The librarian says 'OOOK?' Do you:",
            'choices' => [
                [
                    'text' => "Return the book and apologise",
                    'outcome' => "Your apology is accepted, but you are fined.",
                    'heartsLost' => 1
                ],
                [
                    'text' => "Borrow the recommended book",
                    'outcome' => "You borrow the book and walk towards the exit.",
                    'heartsLost' => 0
                ]
            ]
        ],
    ];
    /**
     * Game Logic.
     */
    public function play()
    {
        echo "\nWelcome to the Dungeon Adventure!\n";
        while ($this->alive && $this->roomIndex < count($this->rooms)) {
            $this->playRoom($this->rooms[$this->roomIndex], $this->roomIndex + 1);
            if ($this->hearts <= 0) {
                $this->alive = false;
                echo "\nYou have lost all your hearts. Game over!\n";
            } else {
                $this->roomIndex++;
            }
        }

        if ($this->alive) {
            echo "\nCongratulations! You made it out alive with {$this->hearts} heart(s) left.\n";
        }
    }

    /**
     * Room Logic.
     */
    private function playRoom(array $room, int $roomNumber)
    {
        echo "\nRoom {$roomNumber}\n" . $room['flavour'] . "\n";
        echo $room['question'] . "\n";

        foreach ($room['choices'] as $i => $choice) {
            echo "  " . ($i + 1) . ". {$choice['text']}\n";
        }

        do {
            echo "> ";
            $input = trim(fgets(STDIN));
        } while (!is_numeric($input) || !isset($room['choices'][$input - 1]));

        $selected = $room['choices'][$input - 1];
        echo $selected['outcome'] . "\n";

        $change = $selected['heartsLost'];
        if ($change > 0) {
            $this->hearts -= $change;
            echo "(You lost {$change} heart(s). Hearts remaining: {$this->hearts})\n";
        } elseif ($change < 0) {
            $healed = min(abs($change), 3 - $this->hearts);
            $this->hearts += $healed;
            echo "(You gained {$healed} heart(s). Hearts now: {$this->hearts})\n";
        }
    }
}

$game = new Game();
$game->play();
