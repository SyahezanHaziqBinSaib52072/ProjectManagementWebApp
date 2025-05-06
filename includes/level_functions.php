<?php
function determineLevel($points) {
    if ($points >= 200) return 6;
    elseif ($points >= 150) return 5;
    elseif ($points >= 100) return 4;
    elseif ($points >= 50) return 3;
    elseif ($points >= 20) return 2;
    else return 1;
}

function levelToMinPoints($level) {
    return match ($level) {
        6 => 200,
        5 => 150,
        4 => 100,
        3 => 50,
        2 => 20,
        default => 0
    };
}
?>
