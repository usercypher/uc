<?php 

class Asset {
    private $css = array();
    private $js = array();
    private $indent = '';

    public function setIndent($indent) {
        $this->indent = $indent;
    }

    public function addCss($cssFile, $attributes = array()) {
        $this->css[] = array(
            'file' => $cssFile,
            'attributes' => $attributes
        );
    }

    public function addJs($jsFile, $attributes = array()) {
        $this->js[] = array(
            'file' => $jsFile,
            'attributes' => $attributes
        );
    }

    public function getCss() {
        $cssLinks = PHP_EOL;
        foreach ($this->css as $css) {
            $cssLinks .= $this->indent . "<link rel='stylesheet' href='" . $css['file'] . "'";
            foreach ($css['attributes'] as $key => $value) {
                $cssLinks .= " " . $key . "='" . $value . "'";
            }
            $cssLinks .= ">" . PHP_EOL; 
        }
        return $cssLinks;
    }

    public function getJs() {
        $jsScripts = PHP_EOL;
        foreach ($this->js as $js) {
            $jsScripts .= $this->indent . "<script src='" . $js['file'] . "'";
            foreach ($js['attributes'] as $key => $value) {
                $jsScripts .= " " . $key . "='" . $value . "'";
            }
            $jsScripts .= "></script>" . PHP_EOL;  
        }
        return $jsScripts;
    }
}
?>