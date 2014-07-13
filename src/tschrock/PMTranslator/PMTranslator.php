<?php

namespace tschrock\PMTranslator;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;

class PMTranslator extends PluginBase {

    const CONFIG_TRANSTO = "defaultTo";
    const CONFIG_TRANSFROM = "defaultFrom";
    const CONFIG_APIURL = "baseUrl";
    const CONFIG_USERAGENT = "UserAgent";
    const CONFIG_DEBUG = "debug";

    var $threadedCurl;

    public function __construct() {
        
    }

    public function onLoad() {
        
    }

    public function onEnable() {
        $this->threadedCurl = new ThreadedCurl();
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new CheckCurlPluginTask($this), 20);

        $this->saveDefaultConfig();
        $this->reloadConfig();
    }

    public function onDisable() {
        $this->threadedCurl->clearAllRequests();
        unset($this->threadedCurl);
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
        switch ($command->getName()) {
            case "translate":

                if (count($args) > 3 && $args[1] == "to") {
                    $fromlang = $args[0];
                    $tolang = $args[2];
                    $offset = 3;
                } elseif (count($args) > 2 && $args[0] == "to") {
                    $fromlang = $this->getConfig()->get("defaultFrom");
                    $tolang = $args[1];
                    $offset = 2;
                } elseif (count($args) > 0) {
                    $fromlang = $this->getConfig()->get("defaultFrom");
                    $tolang = $this->getConfig()->get("defaultTo");
                    $offset = 0;
                } else {
                    $sender->sendMessage("Usage: /translate (From Language) to <To Language> <Text to be translated>");
                    return true;
                }

                $text = implode(" ", array_slice($args, $offset));

                $langTo = PMTranslator::parseLang($tolang);
                $langFrom = PMTranslator::parseLang($fromlang);

                $translateurl = $this->getConfig()->get("baseUrl") . "&text=" . urlencode($text) . "&hl=$langTo&sl=$langFrom";
                
                $translateOpts = array(
                    CURLOPT_USERAGENT => $this->getConfig()->get("UserAgent"),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FAILONERROR => true,
                        );
                
                if ($this->getConfig()->get(self::CONFIG_DEBUG) === true){
                    $sender->sendMessage("[PMTranslator] Debug enabled; Testing multithreading: downloading 20MB test file at 1MB/sec. (That's a 20 second download)");
                    $sender->sendMessage("[PMTranslator] If you can still do stuff, that means threading works. :)");
                    $translateurl = "http://www.tschrock.net/testfiles/20MB.dat";
                    $translateOpts[CURLOPT_CONNECTTIMEOUT] = 1000;
                    $translateOpts[CURLOPT_MAX_RECV_SPEED_LARGE] = 1048576;
                }
                
                

                $this->threadedCurl->startRequest($translateurl, __NAMESPACE__ . '\PMTranslator::onRequestDone', $translateOpts, $sender);
                return true;
            default:
                return false;
        }
    }

    public static function onRequestDone($content, $url, $ch, CommandSender $sender) {

        if ($content === false || !is_string($content)) {
            $sender->sendMessage("[PMTranslator] Could not translate text");
        } else {
            $Jdecode = json_decode($content, true);

            if (!is_null($Jdecode) && isset($Jdecode["sentences"]) && isset($Jdecode["sentences"][0]) && isset($Jdecode["sentences"][0]["trans"])) {
                $sender->sendMessage("[PMTranslator] >" . $Jdecode["sentences"][0]["trans"]);
            } else {
                $sender->sendMessage("[PMTranslator] Could not translate text");
            }
        }
    }

    public static function parseLang($language) {
        $lang = array_search(strtolower($language), PMTranslator::$langCodes);
        if (is_null($lang) || $lang === false) {
            $lang = $language;
        }
        return $lang;
    }

    private static $langCodes = array(
        "af" => "afrikaans",
        "ak" => "akan",
        "sq" => "albanian",
        "am" => "amharic",
        "ar" => "arabic",
        "hy" => "armenian",
        "az" => "azerbaijani",
        "eu" => "basque",
        "be" => "belarusian",
        "bem" => "bemba",
        "bn" => "bengali",
        "bh" => "bihari",
        "xx-bork" => "bork",
        "bs" => "bosnian",
        "br" => "breton",
        "bg" => "bulgarian",
        "km" => "cambodian",
        "ca" => "catalan",
        "chr" => "cherokee",
        "ny" => "chichewa",
        "zh-cn" => "chinese_simplified",
        "zh-tW" => "chinese_traditional",
        "co" => "corsican",
        "hr" => "croatian",
        "cs" => "czech",
        "da" => "danish",
        "nl" => "dutch",
        "xx-elmer" => "elmer_fudd",
        "en" => "english",
        "eo" => "esperanto",
        "et" => "estonian",
        "ee" => "ewe",
        "fo" => "faroese",
        "tl" => "filipino",
        "fi" => "finnish",
        "fr" => "french",
        "fy" => "frisian",
        "gaa" => "ga",
        "gl" => "galician",
        "ka" => "georgian",
        "de" => "german",
        "el" => "greek",
        "gn" => "guarani",
        "gu" => "gujarati",
        "xx-hacker" => "hacker",
        "ht" => "haitian_creole",
        "ha" => "hausa",
        "haw" => "hawaiian",
        "iw" => "hebrew",
        "hi" => "hindi",
        "hu" => "hungarian",
        "is" => "icelandic",
        "ig" => "igbo",
        "id" => "indonesian",
        "ia" => "interlingua",
        "ga" => "irish",
        "it" => "italian",
        "ja" => "japanese",
        "jw" => "javanese",
        "kn" => "kannada",
        "kk" => "kazakh",
        "rw" => "kinyarwanda",
        "rn" => "kirundi",
        "xx-klingon" => "klingon",
        "kg" => "kongo",
        "ko" => "korean",
        "kri" => "krio",
        "ku" => "kurdish",
        "ckb" => "kurdish_soranî",
        "ky" => "kyrgyz",
        "lo" => "laothian",
        "la" => "latin",
        "lv" => "latvian",
        "ln" => "lingala",
        "lt" => "lithuanian",
        "loz" => "lozi",
        "lg" => "luganda",
        "ach" => "luo",
        "mk" => "macedonian",
        "mg" => "malagasy",
        "ms" => "malay",
        "ml" => "malayalam",
        "mt" => "maltese",
        "mi" => "maori",
        "mr" => "marathi",
        "mfe" => "mauritian_creole",
        "mo" => "moldavian",
        "mn" => "mongolian",
        "sr-me" => "montenegrin",
        "ne" => "nepali",
        "pcm" => "nigerian_pidgin",
        "nso" => "northern_sotho",
        "no" => "norwegian",
        "nn" => "norwegian_nynorsk",
        "oc" => "occitan",
        "or" => "oriya",
        "om" => "oromo",
        "ps" => "pashto",
        "fa" => "persian",
        "xx-pirate" => "pirate",
        "pl" => "polish",
        "pt-br" => "portuguese_brazil",
        "pt-pt" => "portuguese",
        "pa" => "punjabi",
        "qu" => "quechua",
        "ro" => "romanian",
        "rm" => "romansh",
        "nyn" => "runyakitara",
        "ru" => "russian",
        "gd" => "scots_gaelic",
        "sr" => "serbian",
        "sh" => "serbo-croatian",
        "st" => "sesotho",
        "tn" => "setswana",
        "crs" => "seychellois_creole",
        "sn" => "shona",
        "sd" => "sindhi",
        "si" => "sinhalese",
        "sk" => "slovak",
        "sl" => "slovenian",
        "so" => "somali",
        "es" => "spanish",
        "es-419" => "spanish_latin_american",
        "su" => "sundanese",
        "sw" => "swahili",
        "sv" => "swedish",
        "tg" => "tajik",
        "ta" => "tamil",
        "tt" => "tatar",
        "te" => "telugu",
        "th" => "thai",
        "ti" => "tigrinya",
        "to" => "tonga",
        "lua" => "tshiluba",
        "tum" => "tumbuka",
        "tr" => "turkish",
        "tk" => "turkmen",
        "tw" => "twi",
        "ug" => "uighur",
        "uk" => "ukrainian",
        "ur" => "urdu",
        "uz" => "uzbek",
        "vi" => "vietnamese",
        "cy" => "welsh",
        "wo" => "wolof",
        "xh" => "xhosa",
        "yi" => "yiddish",
        "yo" => "yoruba",
        "zu" => "zulu"
    );

}
