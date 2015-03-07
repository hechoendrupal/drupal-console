<?php

namespace Drupal\AppConsole\Console;

use Symfony\Component\Console\Shell as BaseShell;

class Shell extends BaseShell
{
    /**
     * Returns the shell header.
     *
     * @return string The header string
     */
    protected function getHeader()
    {
        return <<<EOF
<fg=blue>
                    /`
                    +o/`
                   -oooo/.
                  .+oooooo/-
                ./oooooo+//::`
            `.:+ooooo/-`
         .:+ooooooo+.     `...`
      ./+ooooooooo/    ./oooooo+-
    -+ooooooooooo+`   -oooooooooo/    `
  `/ooooooooooooo/    +ooooooooooo.   .+.
 `+oooooooooooooo+    /ooooooooooo.   -oo/`
 +oooooooooooooooo-   `/oooooooo+.    +ooo+`
:oooooooooooooooo+/`    `-////:.    `+ooooo+`
+oooooooooooo+:.`                 `:+ooooooo:
ooooooooooo/`                  .:+oooooooooo+
ooooooooo+`      `.-::--`       :oooooooooooo
/ooooooo/      -+oooooooo+:`     -oooooooooo+
.oooooo+`    `+oooooooooooo+.     /ooooooooo-
 :ooooo:     /oooooooooooooo+`    .oooooooo/
  /oooo-     +ooooooooooooooo.    `ooooooo/
   :ooo:     :oooooooooooooo+     .oooooo:
    `/oo`     :oooooooooooo+`     /oooo/.
      ./+`     .:+ooooooo/-      :ooo/.
        `-`       `.....       `/o/-`
                             `-:.`
</fg=blue>
EOF
        . parent::getHeader();
    }
}
