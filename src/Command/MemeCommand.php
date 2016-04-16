<?php
namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Command;

/**
 * Ported by Greg Anderson.
 * Original code Copyright (C) 2013 by Muazzam Ali
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
class MemeCommand extends Command
{
    private $upperText;
    private $lowerText;
    private $color;
    private $font;
    private $im;
    private $imgSize;

    /**
     * Generate a meme
     *
     * @command generate:meme
     * @param string $topMessage The message to place at the top of the image.
     * @param string $bottomMessage The message to place at the bottom of the image.
     * @param string $image The path to the meme image to use.
     * @default $image meme.png
     * @option string $out The file to write the generated meme to.
     * @default $out generated-meme.png
     * @usage generate:meme "I don't always make memes" "But when I do, I use the command line" my-source-image.png
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $topMessage = $input->getArgument('topMessage');
        $bottomMessage = $input->getArgument('bottomMessage');
        $memeImage = $input->getArgument('image');
        $outputFile = $input->getOption('out');

        return $this->generateMeme($topMessage, $bottomMessage, $memeImage, $outputFile);
    }

    public function generateMeme(
        $topMessage,
        $bottomMessage,
        $memeImage = 'meme.png',
        $outputFile = 'generated-meme.png'
    ) {
        $finfo = new \finfo(FILEINFO_MIME);
        if ($memeImage[0] != '/') {
            $memeImage = __DIR__ . "/$memeImage";
        }
        $mime = $finfo->file($memeImage);
        if (substr($mime, 0, 5) != "image") {
            exit;
        }
        $this->createImage($memeImage);

        // Impact font is used on the Mac; for other platforms,
        // download Anton.ttf from:
        //   https://googlefontdirectory.googlecode.com/hg/ofl/anton/
        // Place Anton.ttf in ~/.fonts.
        $this->font = $this->locateFont(['Impact.ttf', 'Anton.ttf']);

        $this->setUpperText($topMessage);
        $this->setLowerText($bottomMessage);
        $this->processImg($outputFile);

        return 0;
    }

    protected function locateFont($fontNames)
    {
        // Look for fonts in a few locations, like the Fonts
        // directory and the cwd.
        $fontLocations = [
            '/Library/Fonts/',
            getenv('HOME') . '/.fonts',
            './',
        ];
        foreach ($fontNames as $font) {
            foreach ($fontLocations as $location) {
                $trialFontPath = "$location/$font";
                if (file_exists($trialFontPath)) {
                    return $trialFontPath;
                }
            }
        }
    }

    protected function setUpperText($txt)
    {
        $this->upperText = strtoupper($txt);
    }

    protected function setLowerText($txt)
    {
        $this->lowerText = strtoupper($txt);
    }

    private function getHorizontalTextAlignment($imgWidth, $topRightPixelOfText)
    {
        return \ceil(($imgWidth - $topRightPixelOfText) / 2);
    }

    private function checkTextWidthExceedImage($imgWidth, $fontWidth)
    {
        return ($imgWidth < $fontWidth + 20);
    }

    private function getFontPlacementCoordinates($text, $fontSize)
    {
        /* 		returns
         *      Array
         *      (
         *          [0] => ? // lower left X coordinate
         *          [1] => ? // lower left Y coordinate
         *          [2] => ? // lower right X coordinate
         *          [3] => ? // lower right Y coordinate
         *          [4] => ? // upper right X coordinate
         *          [5] => ? // upper right Y coordinate
         *          [6] => ? // upper left X coordinate
         *          [7] => ? // upper left Y coordinate
         *      )
         * */
        return \imagettfbbox($fontSize, 0, $this->font, $text);
    }

    private function returnImageFromPath($path)
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext == 'jpg' || $ext == 'jpeg') {
            return \imagecreatefromjpeg($path);
        } elseif ($ext == 'png') {
            return \imagecreatefrompng($path);
        } elseif ($ext == 'gif') {
            return \imagecreatefromgif($path);
        }

        return false;
    }

    protected function createImage($path, $color = array(255, 255, 255))
    {
        $this->im = $this->returnImageFromPath($path);
        if (!$this->im) {
            return;
        }
        $this->imgSize = \getimagesize($path); //http://php.net/manual/en/function.getimagesize.php

        $this->color = \imagecolorallocate($this->im, $color[0], $color[1], $color[2]);
        \imagecolortransparent($this->im, $this->color);
    }

    private function workOnImage($text, $size, $type)
    {
        $TextHeight = ($type == "upper") ? $size + 35 : $this->imgSize[1] - 20;

        while (1) {
            //get coordinate for the text
            $coords = $this->getFontPlacementCoordinates($text, $size);

            // place the text in center
            $TextX = $this->getHorizontalTextAlignment($this->imgSize[0], $coords[4]);

            //check if the text does not exceed image width if yes then repeat with size = size - 1
            if ($this->checkTextWidthExceedImage($this->imgSize[0], $coords[2] - $coords[0])) {
                //if top text take it up as font size decreases, if bottom text take it down as font size decreases
                $TextHeight += ($type == "upper") ? - 1 : 1;

                if ($size == 10) {
                    //if text size is reached to lower limit and still it is exceeding image width start breaking into lines
                    if ($type == "upper") {
                        $this->upperText = $this->returnMultipleLinesText($text, $type, 16);
                        $text = $this->upperText;
                        return;
                    } else {
                        $this->lowerText = $this->returnMultipleLinesText($text, $type, $this->imgSize[1] - 20);
                        $text = $this->lowerText;
                        return;
                    }
                } else {
                    $size -=1;
                }
            } else {
                break;
            }
        }

        //$this->placeTextOnImage($this->im, $size, $TextX, $TextHeight, $this->font, (($type == "upper") ? $this->upperText : $this->lowerText));
        $angle = 0;
        $strokecolor = 0;
        $this->imagettfstroketext($this->im, $size, $angle, $TextX, $TextHeight, $this->color, $strokecolor, $this->font, (($type == "upper") ? $this->upperText : $this->lowerText), $px = $size / 15);
    }

    private function placeTextOnImage($img, $fontsize, $Xlocation, $Textheight, $font, $text)
    {
        \imagettftext($this->im, $fontsize, 0, $Xlocation, $Textheight, (int) $this->color, $font, $text);
    }

    /**
     * Writes the given text with a border into the image using TrueType fonts.
     * @author John Ciacia
     * @param image An image resource
     * @param size The font size
     * @param angle The angle in degrees to rotate the text
     * @param x Upper left corner of the text
     * @param y Lower left corner of the text
     * @param textcolor This is the color of the main text
     * @param strokecolor This is the color of the text border
     * @param fontfile The path to the TrueType font you wish to use
     * @param text The text string in UTF-8 encoding
     * @param px Number of pixels the text border will be
     * @see http://us.php.net/manual/en/function.imagettftext.php
     */
    protected function imagettfstroketext(&$image, $size, $angle, $x, $y, &$textcolor, &$strokecolor, $fontfile, $text, $px)
    {
        for ($c1 = ($x - abs($px)); $c1 <= ($x + abs($px)); $c1++) {
            for ($c2 = ($y - abs($px)); $c2 <= ($y + abs($px)); $c2++) {
                $bg = \imagettftext($image, $size, $angle, $c1, $c2, $strokecolor, $fontfile, $text);
            }
        }

        return \imagettftext($image, $size, $angle, $x, $y, $textcolor, $fontfile, $text);
    }

    private function returnMultipleLinesText($text, $type, $textHeight)
    {
        //breaks the whole sentence into multiple lines according to the width of the image.
        //break sentence into an array of words by using the spaces as params
        $brokenText = explode(" ", $text);
        $finalOutput = "";

        if ($type != "upper") {
            $textHeight = $this->imgSize[1] - ((count($brokenText) / 2) * 3);
        }

        for ($i = 0; $i < count($brokenText); $i++) {
            $temp = $finalOutput;
            $finalOutput.= $brokenText[$i] . " ";
            // this will help us to keep the last word in hand if this word is the cause of text exceeding the image size.
            // We will be using this to append in next line.
            //check if word is too long i.e wider than image width
            //get the sentence(appended till now) placement coordinates
            $dimensions = $this->getFontPlacementCoordinates($finalOutput, 10);

            //check if the sentence (till now) is exceeding the image with new word appended
            if ($this->checkTextWidthExceedImage($this->imgSize[0], $dimensions[2] - $dimensions[0])) { //yes it is then
                // append the previous sentence not with the new word  ( new word == $brokenText[$i] )
                $dimensions = $this->getFontPlacementCoordinates($temp, 10);
                $locx = $this->getHorizontalTextAlignment($this->imgSize[0], $dimensions[4]);
                $this->placeTextOnImage($this->im, 10, $locx, $textHeight, $this->font, $temp);
                $finalOutput = $brokenText[$i];
                $textHeight +=13;
            }

            //if this is the last word append this also.The previous if will be true if the last word will have no room
            if ($i == count($brokenText) - 1) {
                $dimensions = $this->getFontPlacementCoordinates($finalOutput, 10);
                $locx = $this->getHorizontalTextAlignment($this->imgSize[0], $dimensions[4]);
                $this->placeTextOnImage($this->im, 10, $locx, $textHeight, $this->font, $finalOutput);
            }
        }
        return $finalOutput;
    }

    protected function processImg($imgOut = "abc.jpg")
    {
        if ($this->lowerText != "") {
            $this->workOnImage($this->lowerText, 30, "lower");
        }

        if ($this->upperText != "") {
            $this->workOnImage($this->upperText, $this->imgSize[1] / 20, "upper");
        }

        $maxWidth = 1000;
        if ($this->imgSize[0] > $maxWidth) {
            $newHeight = ($this->imgSize[1] / $this->imgSize[0]) * $maxWidth;
            $tmp = \imagecreatetruecolor($maxWidth, $newHeight);
            \imagecopyresampled($tmp, $this->im, 0, 0, 0, 0, $maxWidth, $newHeight, $this->imgSize[0], $this->imgSize[1]);
            \imagedestroy($this->im);
            $this->im = $tmp;
        }

        \imagejpeg($this->im, $imgOut);
        \imagedestroy($this->im);
    }
}
