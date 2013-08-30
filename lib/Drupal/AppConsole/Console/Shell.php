<?php

namespace Drupal\AppConsole\Console;

use Symfony\Component\Console\Shell as BaseShell;

/**
 * Shell.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
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
                       ;
                       ft
                      LWii
                      i#i;G
                     tWWiiif
                    t##fiiii,
                   t##Eiiiiii;G
                 jE##DDiiiiiiiiiij
                ;W###DDiiiiiiiiii;f
             #t######Diiiiiiiiiiiiiij
            tf######Djiiiiiiiiiiiiiiij
           ;#######DLiiiiiiiiiiiiiiiii,
         jj######KDLiiiiiiiiiiiiiiiiiii;L
         j######EDjiiiiiiiiiiiiiiiiiiiii;
        t#####KDDiiiiiiiiiiiiiiiiiiiiiiii,
       i#####EDDiiiiiiiiiiiiiiiiiiiiiiiiii;
      fW###DDDiiiiiiiiiiiiiiiiiiiiiiiiiiii;f
      ;DKDDDjiiiiiiiiiiiiiiiiiiiiiiiiiiiii;,
     ;jDDDjiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii;;;
     iijtiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii;;,
    tiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii;;;;j
    ;iiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii;;;;,
    iiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii;;;;;
   Liiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii;;;;;;
   jiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii;;;;;;L
   iiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii;;;;;;;f
   iiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii;;;;;;;;t
   ,iiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii;;;;;;;;;,
   ,iiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii;;;;;;;;;,
   ,iiiiiiiiiiiit#######Eiiiiiiiiii;;;;;;GKi;;,
   iiiiiiiiiiii############iiiiiii;;;;;######;i
   tiiiiiiiiii##############iiiii;;;;;#######;i
   GiiiiiiiiiL###############Ei;;;;;K########;D
    iiiiiiiii#################E;;;;W#########;
    iiiiiiiiL################################;
    ,iiiiiiiE################################,
    GiiiiiiiK#################G;;;f##########i
     ;iiiiiif###############W;;;;;;;########K
     ;iiiiiii##############t;;;;;;;;;#######;
      ;iiiiiit###########i;;;L####;;;i#####i
       ;;iiiiiK########E;;;;j##KW#W;;;;####t
       t;;;;;;;;;fGDf;;;;;;##;;;;;#j;;;;i;,
        i;;;;;;;;;;;;;;;;;;D;;;;;;L;;;;;;,G
         ;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;,
          ;;;;;;;;;;;;;;;;;;;;;;;;;;;;;,j
           j;;;;;;;;;;i##f;;;;;;;L##;;,
             t;;;;;;;;;i##########;;,
              j,;;;;;;;;;iLKWKDf;;;;
                 t,;;;;;;;;;;;;;;f
                   D;,,;;;;,,;j
</fg=blue>
EOF
        .parent::getHeader();
    }
}
