<?php

namespace Database\Seeders;

use App\Models\Colours;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $colours = array (
            0 => 
            array (
              'name' => 'SURREAL EFFECTS BLUE HAMMER COLOUR VEIN RIPPLE',
            ),
            1 => 
            array (
              'name' => 'EXCEL™ IRONSTONE®  MATT',
            ),
            2 => 
            array (
              'name' => 'BLACK HAMMERTONE',
            ),
            3 => 
            array (
              'name' => 'ANODIC SILVER GREY MATT',
            ),
            4 => 
            array (
              'name' => 'ADMIRALTY GLOSS',
            ),
            5 => 
            array (
              'name' => 'ANOTEC MID BRONZE',
            ),
            6 => 
            array (
              'name' => 'AZURE GREY SATIN',
            ),
            7 => 
            array (
              'name' => 'SATIN BLACK',
            ),
            8 => 
            array (
              'name' => 'BLUE WEAVE',
            ),
            9 => 
            array (
              'name' => 'CRYSTAL JEWELS',
            ),
            10 => 
            array (
              'name' => 'COLORBOND WILDERNESS',
            ),
            11 => 
            array (
              'name' => 'CABANA GREEN',
            ),
            12 => 
            array (
              'name' => 'COLOURBOND PLANTATION',
            ),
            13 => 
            array (
              'name' => 'HAMMERSLEY BROWN SATIN',
            ),
            14 => 
            array (
              'name' => 'GLITTER ALL',
            ),
            15 => 
            array (
              'name' => 'GREY BASE MANNEX',
            ),
            16 => 
            array (
              'name' => 'GJ DARK BRONZE',
            ),
            17 => 
            array (
              'name' => 'IVORY COAST',
            ),
            18 => 
            array (
              'name' => 'KIWI FRUIT',
            ),
            19 => 
            array (
              'name' => 'LEXICON QUARTER MATT',
            ),
            20 => 
            array (
              'name' => 'LIGHT TOPAZ SATIN',
            ),
            21 => 
            array (
              'name' => 'LOFT SATIN',
            ),
            22 => 
            array (
              'name' => 'METROPOLIS CLARET PEARL SATIN',
            ),
            23 => 
            array (
              'name' => 'METROPOLIS CHARCOAL',
            ),
            24 => 
            array (
              'name' => 'PRECIOUS  GOLD SATIN',
            ),
            25 => 
            array (
              'name' => 'PALLID SKY MATT',
            ),
            26 => 
            array (
              'name' => 'RIPLLE GYPSY BEIGE',
            ),
            27 => 
            array (
              'name' => 'RAGENCY GREY MATT',
            ),
            28 => 
            array (
              'name' => 'ROSBERRY GREY GLOSS',
            ),
            29 => 
            array (
              'name' => 'SAND SATIN',
            ),
            30 => 
            array (
              'name' => 'TEAL GLOSS',
            ),
            31 => 
            array (
              'name' => 'OFF WHITE',
            ),
            32 => 
            array (
              'name' => 'WINEBERRY',
            ),
            33 => 
            array (
              'name' => 'YELLOW',
            ),
            34 => 
            array (
              'name' => 'ZUES CHARCOAL',
            ),
            35 => 
            array (
              'name' => 'ZUES TIMBERLAND',
            ),
            36 => 
            array (
              'name' => 'ZUES WHITE GLOSS',
            ),
            37 => 
            array (
              'name' => '810 CLEAR GLOSS',
            ),
            38 => 
            array (
              'name' => 'ANODIC SILVER GREY',
            ),
            39 => 
            array (
              'name' => 'ANODIC NIGHTHAWK MATT',
            ),
            40 => 
            array (
              'name' => 'ANTIQUE SILVER',
            ),
            41 => 
            array (
              'name' => 'APO GREY SCYLLA',
            ),
            42 => 
            array (
              'name' => 'AUBERGINE SATIN',
            ),
            43 => 
            array (
              'name' => 'ARROWHEAD GLOSS',
            ),
            44 => 
            array (
              'name' => 'ANTIQUE COPPER',
            ),
            45 => 
            array (
              'name' => 'ANODIC SLATE GREY MATT',
            ),
            46 => 
            array (
              'name' => 'DARK GREY MATT',
            ),
            47 => 
            array (
              'name' => 'BRONZE OLIVE MATT',
            ),
            48 => 
            array (
              'name' => 'BUSHLAND SATIN',
            ),
            49 => 
            array (
              'name' => 'BLUE BLOOD',
            ),
            50 => 
            array (
              'name' => 'DOMINION BLACK',
            ),
            51 => 
            array (
              'name' => 'URBAN BLACK',
            ),
            52 => 
            array (
              'name' => 'COLOURBOND HEADLAND',
            ),
            53 => 
            array (
              'name' => 'COLOURBOND BLUE RIDGE',
            ),
            54 => 
            array (
              'name' => 'CHARCOAL',
            ),
            55 => 
            array (
              'name' => 'DEEP GLOSS PURPLE',
            ),
            56 => 
            array (
              'name' => 'COLOURBOND ESTATE',
            ),
            57 => 
            array (
              'name' => 'COLOURBOND SANDBANK SATIN',
            ),
            58 => 
            array (
              'name' => 'FRENCH CHAMPAGNE',
            ),
            59 => 
            array (
              'name' => 'HAMMERSLEY BROWN SATIN',
            ),
            60 => 
            array (
              'name' => 'HOT CHOCOLATE',
            ),
            61 => 
            array (
              'name' => 'LIMERICK SATIN',
            ),
            62 => 
            array (
              'name' => 'OCEAN MIST',
            ),
            63 => 
            array (
              'name' => 'SMOKEY GLASS SATIN',
            ),
            64 => 
            array (
              'name' => 'INTENSITY SUNSHINE',
            ),
            65 => 
            array (
              'name' => 'NATURAL SILVER',
            ),
            66 => 
            array (
              'name' => 'TEA TREE',
            ),
            67 => 
            array (
              'name' => 'WATRMELON',
            ),
            68 => 
            array (
              'name' => 'ZUES TALC SATIN',
            ),
            69 => 
            array (
              'name' => 'ZUES LUNAR ECLIPSE',
            ),
            70 => 
            array (
              'name' => 'ZUES MONUMENT',
            ),
            71 => 
            array (
              'name' => 'ZUES GREY',
            ),
            72 => 
            array (
              'name' => 'ZUES MATT BLACK',
            ),
            73 => 
            array (
              'name' => 'JOTUN RIPPLE ORANGE',
            ),
            74 => 
            array (
              'name' => 'ZUES APPLIANCE WHITE',
            ),
            75 => 
            array (
              'name' => 'ALMOND IVORY GLOSS',
            ),
            76 => 
            array (
              'name' => 'ANODIC BRONZE SATIN',
            ),
            77 => 
            array (
              'name' => 'ANODIC CHAMPAGNE MATT',
            ),
            78 => 
            array (
              'name' => 'ANODIC CLEAR MATT',
            ),
            79 => 
            array (
              'name' => 'ANODIC DARK GREY MATT',
            ),
            80 => 
            array (
              'name' => 'ANODIC NATURAL MATT',
            ),
            81 => 
            array (
              'name' => 'ANODIC NATURAL MATT',
            ),
            82 => 
            array (
              'name' => 'ANODIC OFF WHITE MATT',
            ),
            83 => 
            array (
              'name' => 'ANODIC SILVER GREY MATT',
            ),
            84 => 
            array (
              'name' => 'APO GREY SATIN',
            ),
            85 => 
            array (
              'name' => 'APPLIANCE WHITE SATIN',
            ),
            86 => 
            array (
              'name' => 'ASTEROID PEARL MATT',
            ),
            87 => 
            array (
              'name' => 'BARLEY GLOSS',
            ),
            88 => 
            array (
              'name' => 'BLACK CUSTOM MATT LOW MAR',
            ),
            89 => 
            array (
              'name' => 'BLACK INK FLAT MATT',
            ),
            90 => 
            array (
              'name' => 'BLACK INK FLAT MATT',
            ),
            91 => 
            array (
              'name' => 'BLACK SATIN',
            ),
            92 => 
            array (
              'name' => 'BLACK SATIN',
            ),
            93 => 
            array (
              'name' => 'BLACK TEXTURA™',
            ),
            94 => 
            array (
              'name' => 'BLACK TEXTURA™',
            ),
            95 => 
            array (
              'name' => 'BLAZE BLUE GLOSS',
            ),
            96 => 
            array (
              'name' => 'BONDI BLUE GLOSS',
            ),
            97 => 
            array (
              'name' => 'BRIGHT SILVER',
            ),
            98 => 
            array (
              'name' => 'BRIGHT WHITE GLOSS',
            ),
            99 => 
            array (
              'name' => 'BRILLIANCE FLAT MATT',
            ),
            100 => 
            array (
              'name' => 'BRILLIANT YELLOW GLOSS',
            ),
            101 => 
            array (
              'name' => 'BRONZE PEARL MATT',
            ),
            102 => 
            array (
              'name' => 'CHAMPAGNE PEARL MATT',
            ),
            103 => 
            array (
              'name' => 'CHAMPAGNE SHIMMER FLAT MATT',
            ),
            104 => 
            array (
              'name' => 'CHARCOAL MATT',
            ),
            105 => 
            array (
              'name' => 'CHARCOAL PEARL MATT',
            ),
            106 => 
            array (
              'name' => 'CHARCOAL SATIN',
            ),
            107 => 
            array (
              'name' => 'CITI PEARL MATT',
            ),
            108 => 
            array (
              'name' => 'CLARET SATIN',
            ),
            109 => 
            array (
              'name' => 'CLASSIC PEARL WHITE GLOSS',
            ),
            110 => 
            array (
              'name' => 'CORAL BLACK GLOSS',
            ),
            111 => 
            array (
              'name' => 'CUSTOM BLACK MATT',
            ),
            112 => 
            array (
              'name' => 'DARK BRONZE FLAT MATT',
            ),
            113 => 
            array (
              'name' => 'DARK GREY MATT',
            ),
            114 => 
            array (
              'name' => 'DEEP OCEAN TEXTURA™',
            ),
            115 => 
            array (
              'name' => 'DEEP POOL SATIN',
            ),
            116 => 
            array (
              'name' => 'DOESKIN SATIN',
            ),
            117 => 
            array (
              'name' => 'DRIFTWOOD MATT',
            ),
            118 => 
            array (
              'name' => 'DUNE TEXTURA™',
            ),
            119 => 
            array (
              'name' => 'EASYCLEAN CLEAR GLOSS',
            ),
            120 => 
            array (
              'name' => 'EBONY MATT',
            ),
            121 => 
            array (
              'name' => 'ENDURA BRONZE MATT',
            ),
            122 => 
            array (
              'name' => 'ETERNAL SILVER SATIN',
            ),
            123 => 
            array (
              'name' => 'EXCEL™ BASALT® MATT',
            ),
            124 => 
            array (
              'name' => 'EXCEL™ BASALT® SATIN',
            ),
            125 => 
            array (
              'name' => 'EXCEL™ BUSHLAND® MATT',
            ),
            126 => 
            array (
              'name' => 'EXCEL™ BUSHLAND® SATIN',
            ),
            127 => 
            array (
              'name' => 'EXCEL™ CHARCOAL GLOSS',
            ),
            128 => 
            array (
              'name' => 'EXCEL™ CHARCOAL SATIN',
            ),
            129 => 
            array (
              'name' => 'EXCEL™ CLASSIC CREAM® MATT',
            ),
            130 => 
            array (
              'name' => 'EXCEL™ CLASSIC CREAM® SATIN',
            ),
            131 => 
            array (
              'name' => 'EXCEL™ CORAL BLACK GLOSS',
            ),
            132 => 
            array (
              'name' => 'EXCEL™ COTTAGE GREEN ® SATIN',
            ),
            133 => 
            array (
              'name' => 'EXCEL™ COTTAGE GREEN® MATT',
            ),
            134 => 
            array (
              'name' => 'EXCEL™ COVE® MATT',
            ),
            135 => 
            array (
              'name' => 'EXCEL™ DEEP BRUNSWICK GREEN',
            ),
            136 => 
            array (
              'name' => 'EXCEL™ DEEP OCEAN ® SATIN',
            ),
            137 => 
            array (
              'name' => 'EXCEL™ DEEP OCEAN® MATT',
            ),
            138 => 
            array (
              'name' => 'EXCEL™ DOMAIN® MATT',
            ),
            139 => 
            array (
              'name' => 'EXCEL™ DUNE ® SATIN',
            ),
            140 => 
            array (
              'name' => 'EXCEL™ DUNE®  MATT',
            ),
            141 => 
            array (
              'name' => 'EXCEL™ EVENING HAZE® MATT',
            ),
            142 => 
            array (
              'name' => 'EXCEL™ EVENING HAZE® SATIN',
            ),
            143 => 
            array (
              'name' => 'EXCEL™ GULLY® MATT',
            ),
            144 => 
            array (
              'name' => 'EXCEL™ HARVEST®  MATT',
            ),
            145 => 
            array (
              'name' => 'EXCEL™ HAWTHORN GREEN GLOSS',
            ),
            146 => 
            array (
              'name' => 'EXCEL™ HEADLANDS® SATIN',
            ),
            147 => 
            array (
              'name' => 'EXCEL™ HERITAGE GREEN GLOSS',
            ),
            148 => 
            array (
              'name' => 'EXCEL™ IRONSTONE®  SATIN',
            ),
            149 => 
            array (
              'name' => 'EXCEL™ JASPER®  MATT',
            ),
            150 => 
            array (
              'name' => 'EXCEL™ JASPER®  SATIN',
            ),
            151 => 
            array (
              'name' => 'EXCEL™ MANGROVE® MATT',
            ),
            152 => 
            array (
              'name' => 'EXCEL™ MANOR RED®  MATT',
            ),
            153 => 
            array (
              'name' => 'EXCEL™ MANOR RED®  SATIN',
            ),
            154 => 
            array (
              'name' => 'EXCEL™ MONUMENT®  MATT',
            ),
            155 => 
            array (
              'name' => 'EXCEL™ MONUMENT®  SATIN',
            ),
            156 => 
            array (
              'name' => 'EXCEL™ NIGHT SKY® MATT',
            ),
            157 => 
            array (
              'name' => 'EXCEL™ NIGHTSKY®  SATIN',
            ),
            158 => 
            array (
              'name' => 'EXCEL™ PALE EUCALYPT®  SATIN',
            ),
            159 => 
            array (
              'name' => 'EXCEL™ PALE EUCALYPT® MATT',
            ),
            160 => 
            array (
              'name' => 'EXCEL™ PAPERBARK ®  MATT',
            ),
            161 => 
            array (
              'name' => 'EXCEL™ PAPERBARK®  SATIN',
            ),
            162 => 
            array (
              'name' => 'EXCEL™ PEARL WHITE GLOSS',
            ),
            163 => 
            array (
              'name' => 'EXCEL™ PRIMROSE GLOSS',
            ),
            164 => 
            array (
              'name' => 'EXCEL™ RIVERSAND®  MATT',
            ),
            165 => 
            array (
              'name' => 'EXCEL™ SAFETY YELLOW GLOSS',
            ),
            166 => 
            array (
              'name' => 'EXCEL™ SHALE GREY®  SATIN',
            ),
            167 => 
            array (
              'name' => 'EXCEL™ SHALE GREY® MATT',
            ),
            168 => 
            array (
              'name' => 'EXCEL™ SURFMIST®  MATT',
            ),
            169 => 
            array (
              'name' => 'EXCEL™ SURFMIST®  SATIN',
            ),
            170 => 
            array (
              'name' => 'EXCEL™ TERRAIN® MATT',
            ),
            171 => 
            array (
              'name' => 'EXCEL™ TERRAIN® SATIN',
            ),
            172 => 
            array (
              'name' => 'EXCEL™ WALLABY® MATT',
            ),
            173 => 
            array (
              'name' => 'EXCEL™ WALLABY® SATIN',
            ),
            174 => 
            array (
              'name' => 'EXCEL™ WILDERNESS®  SATIN',
            ),
            175 => 
            array (
              'name' => 'EXCEL™ WILDERNESS® MATT',
            ),
            176 => 
            array (
              'name' => 'EXCEL™ WINDSPRAY®  SATIN',
            ),
            177 => 
            array (
              'name' => 'EXCEL™ WINDSPRAY® MATT',
            ),
            178 => 
            array (
              'name' => 'EXCEL™ WOODLAND GREY®  SATIN',
            ),
            179 => 
            array (
              'name' => 'EXCEL™ WOODLAND GREY® MATT',
            ),
            180 => 
            array (
              'name' => 'EXCEL™ YELLOW  GOLD GLOSS',
            ),
            181 => 
            array (
              'name' => 'FLAME RED GLOSS',
            ),
            182 => 
            array (
              'name' => 'FRENCH BLUE GLOSS',
            ),
            183 => 
            array (
              'name' => 'GOLDEN TOUCH PEARL FLAT MATT',
            ),
            184 => 
            array (
              'name' => 'GREY SATIN',
            ),
            185 => 
            array (
              'name' => 'GRIPTEX BLACK',
            ),
            186 => 
            array (
              'name' => 'GRIPTEX YELLOW',
            ),
            187 => 
            array (
              'name' => 'HAMERSLEY BROWN SATIN',
            ),
            188 => 
            array (
              'name' => 'HAMMER MYSTIQUE SILVER',
            ),
            189 => 
            array (
              'name' => 'INTERPON METAPREP™ GREY',
            ),
            190 => 
            array (
              'name' => 'INTERPON PZ 560 ZINC PRIMER',
            ),
            191 => 
            array (
              'name' => 'INTERPON PZ 790 ZINC PRIMER',
            ),
            192 => 
            array (
              'name' => 'IRONSTONE TEXTURA™',
            ),
            193 => 
            array (
              'name' => 'JASPER TEXTURA™',
            ),
            194 => 
            array (
              'name' => 'JAYBIRD SATIN',
            ),
            195 => 
            array (
              'name' => 'LAWN GREEN GLOSS',
            ),
            196 => 
            array (
              'name' => 'LEMON YELLOW GLOSS',
            ),
            197 => 
            array (
              'name' => 'LINEN FLAT MATT',
            ),
            198 => 
            array (
              'name' => 'LOBSTER SATIN',
            ),
            199 => 
            array (
              'name' => 'LUNAR GREY MATT',
            ),
            200 => 
            array (
              'name' => 'LUXE BRONZE PEARL',
            ),
            201 => 
            array (
              'name' => 'LYCRA STRIP GLOSS',
            ),
            202 => 
            array (
              'name' => 'MAGNOLIA GLOSS',
            ),
            203 => 
            array (
              'name' => 'MEDIUM BRONZE FLAT MATT',
            ),
            204 => 
            array (
              'name' => 'MONUMENT FLAT MATT',
            ),
            205 => 
            array (
              'name' => 'MONUMENT SATIN MKII',
            ),
            206 => 
            array (
              'name' => 'MONUMENT TEXTURA™',
            ),
            207 => 
            array (
              'name' => 'MONUMENT(R) MATT',
            ),
            208 => 
            array (
              'name' => 'N42 STORM GREY GLOSS',
            ),
            209 => 
            array (
              'name' => 'NATURAL SHIMMER FLAT MATT',
            ),
            210 => 
            array (
              'name' => 'NOBEL SILVER PEARL(MC) SATIN',
            ),
            211 => 
            array (
              'name' => 'NOTRE DAME GLOSS',
            ),
            212 => 
            array (
              'name' => 'NUANCE SILVER',
            ),
            213 => 
            array (
              'name' => 'OFF WHITE MATT',
            ),
            214 => 
            array (
              'name' => 'OLDE PEWTER SATIN',
            ),
            215 => 
            array (
              'name' => 'OYSTER GREY MATT',
            ),
            216 => 
            array (
              'name' => 'PAPERBARK TEXTURA™',
            ),
            217 => 
            array (
              'name' => 'PEARL WHITE GLOSS',
            ),
            218 => 
            array (
              'name' => 'PEARL WHITE MATT',
            ),
            219 => 
            array (
              'name' => 'PEWTER PEARL SATIN',
            ),
            220 => 
            array (
              'name' => 'POTTERY SATIN',
            ),
            221 => 
            array (
              'name' => 'PRIMROSE GLOSS',
            ),
            222 => 
            array (
              'name' => 'PRIMROSE TEXTURA™',
            ),
            223 => 
            array (
              'name' => 'PURE GOLD FLAT MATT',
            ),
            224 => 
            array (
              'name' => 'PZ GREY SN3 20KG',
            ),
            225 => 
            array (
              'name' => 'RF ISUZU ARC WHITE GLOSS',
            ),
            226 => 
            array (
              'name' => 'RIPPLE 7032 PEBBLE GREY',
            ),
            227 => 
            array (
              'name' => 'RIPPLE APO GREY',
            ),
            228 => 
            array (
              'name' => 'RIPPLE BLACK GLOSS',
            ),
            229 => 
            array (
              'name' => 'RIPPLE BLACK LEATHER MATT',
            ),
            230 => 
            array (
              'name' => 'RIPPLE GRAPHITE',
            ),
            231 => 
            array (
              'name' => 'RIPPLE RAL 7035',
            ),
            232 => 
            array (
              'name' => 'RIPPLE WHITE GLOSS',
            ),
            233 => 
            array (
              'name' => 'RIPPLE X15 ORANGE',
            ),
            234 => 
            array (
              'name' => 'RIVERGUM GLOSS',
            ),
            235 => 
            array (
              'name' => 'SABLE BASS',
            ),
            236 => 
            array (
              'name' => 'SABLE BRILLIANCE',
            ),
            237 => 
            array (
              'name' => 'SABLE CORE TEN',
            ),
            238 => 
            array (
              'name' => 'SABLE MEDIUM BRONZE',
            ),
            239 => 
            array (
              'name' => 'SABLE™ ASTEROID',
            ),
            240 => 
            array (
              'name' => 'SABLE™ BASS',
            ),
            241 => 
            array (
              'name' => 'SABLE™ BLACK',
            ),
            242 => 
            array (
              'name' => 'SABLE™ BRILLIANCE',
            ),
            243 => 
            array (
              'name' => 'SABLE™ CORE TEN™',
            ),
            244 => 
            array (
              'name' => 'SABLE™ GREY NURSE',
            ),
            245 => 
            array (
              'name' => 'SABLE™ SILVER',
            ),
            246 => 
            array (
              'name' => 'SCINTILLATING CHAMPAGNE',
            ),
            247 => 
            array (
              'name' => 'SENSATION GLOSS',
            ),
            248 => 
            array (
              'name' => 'SHAMROCK GREEN GLOSS',
            ),
            249 => 
            array (
              'name' => 'SHOJI WHITE SATIN',
            ),
            250 => 
            array (
              'name' => 'SIGNAL RED GLOSS',
            ),
            251 => 
            array (
              'name' => 'SILVER PEARL MATT',
            ),
            252 => 
            array (
              'name' => 'SILVER TEXTURA™',
            ),
            253 => 
            array (
              'name' => 'SPACE BLUE GLOSS',
            ),
            254 => 
            array (
              'name' => 'STERLING WHITE GLOSS',
            ),
            255 => 
            array (
              'name' => 'STONE BEIGE MATT',
            ),
            256 => 
            array (
              'name' => 'STROMBOLI SATIN',
            ),
            257 => 
            array (
              'name' => 'SUNSTONE BRONZE FLAT MATT',
            ),
            258 => 
            array (
              'name' => 'SURFMIST MATT',
            ),
            259 => 
            array (
              'name' => 'SURFMIST TEXTURA™',
            ),
            260 => 
            array (
              'name' => 'TEXTURE YELLOW',
            ),
            261 => 
            array (
              'name' => 'TIMBERLAND MATT',
            ),
            262 => 
            array (
              'name' => 'TIMBERLAND SATIN',
            ),
            263 => 
            array (
              'name' => 'TITANIUM PEARL MATT',
            ),
            264 => 
            array (
              'name' => 'TITANIUM PEARL SATIN',
            ),
            265 => 
            array (
              'name' => 'TOYOTA GRAPHITE 1G3',
            ),
            266 => 
            array (
              'name' => 'TOYOTA WHITE 040 GLOSS',
            ),
            267 => 
            array (
              'name' => 'TOYOTA WHITE 058 GLOSS',
            ),
            268 => 
            array (
              'name' => 'TRANSFORMER GREY',
            ),
            269 => 
            array (
              'name' => 'VINTAGE SILVER PEARL FLAT MATT',
            ),
            270 => 
            array (
              'name' => 'VIPER GREEN GLOSS',
            ),
            271 => 
            array (
              'name' => 'VIVICA™ ASTEROID PEARL MATT',
            ),
            272 => 
            array (
              'name' => 'VIVICA™ BLACK ONYX GLOSS',
            ),
            273 => 
            array (
              'name' => 'VIVICA™ CHARCOAL METALLIC GLOSS',
            ),
            274 => 
            array (
              'name' => 'VIVICA™ CHARCOAL PEARL MATT',
            ),
            275 => 
            array (
              'name' => 'VIVICA™ CITI MATT',
            ),
            276 => 
            array (
              'name' => 'VIVICA™ MERCURY SILVER GLOSS',
            ),
            277 => 
            array (
              'name' => 'VIVICA™ NOBEL SILVER PEARL SATIN',
            ),
            278 => 
            array (
              'name' => 'VIVICA™ PALLADIUM SILVER PEARL',
            ),
            279 => 
            array (
              'name' => 'VIVICA™ SNOW DUST MATT',
            ),
            280 => 
            array (
              'name' => 'VIVICA™ STORM FRONT MATT',
            ),
            281 => 
            array (
              'name' => 'VIVICA™ TREASURED SILVER PEARL',
            ),
            282 => 
            array (
              'name' => 'VIVICA™ ULTRA SILVER GLOSS',
            ),
            283 => 
            array (
              'name' => 'WEDGEWOOD SATIN',
            ),
            284 => 
            array (
              'name' => 'WHITE BIRCH GLOSS',
            ),
            285 => 
            array (
              'name' => 'WHITE FLAT MATT',
            ),
            286 => 
            array (
              'name' => 'WHITE GLOSS',
            ),
            287 => 
            array (
              'name' => 'WHITE SATIN',
            ),
            288 => 
            array (
              'name' => 'WHITE TEXTURA™',
            ),
            289 => 
            array (
              'name' => 'WIZARD GLOSS',
            ),
            290 => 
            array (
              'name' => 'WOODLAND GREY TEXTURA™',
            ),
            291 => 
            array (
              'name' => 'X15 ORANGE GLOSS',
            ),
            292 => 
            array (
              'name' => 'DURALLOY BARRISTER WHITE SATIN',
            ),
            293 => 
            array (
              'name' => 'DURALLOY COTTAGE GREEN MATT',
            ),
            294 => 
            array (
              'name' => 'DURALLOY BLACK GLOSS',
            ),
            295 => 
            array (
              'name' => 'DURALLOY IRONSTONE SATIN',
            ),
            296 => 
            array (
              'name' => 'DURALLOY ANOTEC OFF WHITE MATT',
            ),
            297 => 
            array (
              'name' => 'ELECTRO BLACK ACE FLAT',
            ),
            298 => 
            array (
              'name' => 'DURALLOY CLASIC CREAM SATIN',
            ),
            299 => 
            array (
              'name' => 'DURALLOY CLASSIC PEARL WHITE GLOSS',
            ),
            300 => 
            array (
              'name' => 'DURALLOY SURFMIST SATIN',
            ),
            301 => 
            array (
              'name' => 'DURALLOY WHITE SATIN',
            ),
            302 => 
            array (
              'name' => 'DURALLOY DUNE SATIN',
            ),
            303 => 
            array (
              'name' => 'ELECTRO SENSATIONAL CHAMPAGNE FLAT',
            ),
            304 => 
            array (
              'name' => 'DURALLOY ANOTEC SILVER GREY MATT',
            ),
            305 => 
            array (
              'name' => 'DURALLOY HAMMERSLEY BROWN SATIN',
            ),
            306 => 
            array (
              'name' => 'DURALLOY MANOR RED SATIN',
            ),
            307 => 
            array (
              'name' => 'DURALLOY BLUE RIDGE SATIN',
            ),
            308 => 
            array (
              'name' => 'SURREAL EFFECTS MATT RIPPLE SILVER SAROUK',
            ),
            309 => 
            array (
              'name' => 'DURALLOY MANOR RED MATT',
            ),
            310 => 
            array (
              'name' => 'DURATEC INTENSITY STORM SATIN',
            ),
            311 => 
            array (
              'name' => 'PRECIOUS SHARP SILVER KINETIC PEARL SATIN',
            ),
            312 => 
            array (
              'name' => 'DURALLOY BLACK GLOSS',
            ),
            313 => 
            array (
              'name' => 'DURALLOY WINDSPRAY SATIN',
            ),
            314 => 
            array (
              'name' => 'DURALLOY PAPERBARK SATIN',
            ),
            315 => 
            array (
              'name' => 'DURALLOY BLACK SATIN',
            ),
            316 => 
            array (
              'name' => 'ALPHATEC YELLOW GOLD GLOSS',
            ),
            317 => 
            array (
              'name' => 'FLUOROSET ALLURE SILVER SATIN',
            ),
            318 => 
            array (
              'name' => 'DURALLOY IRONSTONE MATT',
            ),
            319 => 
            array (
              'name' => 'DURATEC ZEUS TALC SATIN',
            ),
            320 => 
            array (
              'name' => 'DURATEC ELEMENTS MONUMENT FLAT',
            ),
            321 => 
            array (
              'name' => 'DURALLOY IRONSTONE SATIN',
            ),
            322 => 
            array (
              'name' => 'DURALLOY OFF WHITE SATIN',
            ),
            323 => 
            array (
              'name' => 'DURALLOY DEEP OCEAN SATIN',
            ),
            324 => 
            array (
              'name' => 'DURALLOY LIGHT GREY GLOSS',
            ),
            325 => 
            array (
              'name' => 'DURALLOY SURFMIST MATT',
            ),
            326 => 
            array (
              'name' => 'ALPHATEC DARK VIOLET GLOSS',
            ),
            327 => 
            array (
              'name' => 'FLUOROSET XTREME SURFMIST SATIN',
            ),
            328 => 
            array (
              'name' => 'PRECIOUS METROPOLIS STORM SATIN',
            ),
            329 => 
            array (
              'name' => 'DURATEC ETERNITY COPPER METALLIC KINETIC MATT',
            ),
            330 => 
            array (
              'name' => 'DURALLOY BLACK GLOSS',
            ),
            331 => 
            array (
              'name' => 'ALPHATEC COPPER  PEARL SATIN',
            ),
            332 => 
            array (
              'name' => 'DURALLOY MONUMENT SATIN',
            ),
            333 => 
            array (
              'name' => 'SURREAL EFFECTS RED BROWN SCYLLA GLOSS RIPPLE',
            ),
            334 => 
            array (
              'name' => 'ALPHATEC YELLOW GOLD GLOSS',
            ),
            335 => 
            array (
              'name' => 'DURALLOY PALE EUCALYPT SATIN',
            ),
            336 => 
            array (
              'name' => 'DURALLOY EVENING HAZE MATT',
            ),
            337 => 
            array (
              'name' => 'PRECIOUS PEWTER PEARL SATIN',
            ),
            338 => 
            array (
              'name' => 'DURALLOY BLACK MATT',
            ),
            339 => 
            array (
              'name' => 'DURALLOY JASPER SATIN',
            ),
            340 => 
            array (
              'name' => 'DURATEC ZEUS GREY SATIN',
            ),
            341 => 
            array (
              'name' => 'DURALLOY EVENING HAZE SATIN',
            ),
            342 => 
            array (
              'name' => 'DURATEC ETERNITY CHAIN PEARL MATT',
            ),
            343 => 
            array (
              'name' => 'PRECIOUS CITI PEARL MATT',
            ),
            344 => 
            array (
              'name' => 'DURATEC ETERNITY BRONZE PEARL SATIN',
            ),
            345 => 
            array (
              'name' => 'ELECTRO FLAT WHITE FLAT',
            ),
            346 => 
            array (
              'name' => 'DURALLOY MANOR RED SATIN',
            ),
            347 => 
            array (
              'name' => 'ELECTRO FRESH GOLD FLAT',
            ),
            348 => 
            array (
              'name' => 'DURALLOY COTTAGE GREEN SATIN',
            ),
            349 => 
            array (
              'name' => 'ELECTRO DARK BRONZE FLAT',
            ),
            350 => 
            array (
              'name' => 'DURALLOY ANOTEC MID BRONZE MATT',
            ),
            351 => 
            array (
              'name' => 'ALPHATEC BLAZE BLUE GLOSS',
            ),
            352 => 
            array (
              'name' => 'ELECTRO FRESH GOLD FLAT',
            ),
            353 => 
            array (
              'name' => 'ELECTRO BURNISHED COPPER FLAT',
            ),
            354 => 
            array (
              'name' => 'DURALLOY WOODLAND GREY MATT',
            ),
            355 => 
            array (
              'name' => 'DURALLOY MANGROVE SATIN',
            ),
            356 => 
            array (
              'name' => 'DURATEC INTENSITY FLAME GLOSS',
            ),
            357 => 
            array (
              'name' => 'DURATEC INTENSITY MOONLIGHT SATIN',
            ),
            358 => 
            array (
              'name' => 'DURALLOY PALE EUCALYPT MATT',
            ),
            359 => 
            array (
              'name' => 'DURALLOY BLACK SATIN',
            ),
            360 => 
            array (
              'name' => 'DURALLOY SHALE GREY SATIN',
            ),
            361 => 
            array (
              'name' => 'ELECTRO BURNISHED COPPER FLAT',
            ),
            362 => 
            array (
              'name' => 'PRECIOUS SILVER KINETIC PEARL SATIN',
            ),
            363 => 
            array (
              'name' => 'DURALLOY WHITE BIRCH SATIN',
            ),
            364 => 
            array (
              'name' => 'SURREAL EFFECTS MATT RIPPLE BLACK SAROUK',
            ),
            365 => 
            array (
              'name' => 'DURALLOY WALLABY SATIN',
            ),
            366 => 
            array (
              'name' => 'PRECIOUS COPPER KINETIC PEARL SATIN',
            ),
            367 => 
            array (
              'name' => 'DURALLOY BLACK MATT',
            ),
            368 => 
            array (
              'name' => 'DURALLOY CHARCOAL SATIN',
            ),
            369 => 
            array (
              'name' => 'DURALLOY MONUMENT SATIN',
            ),
            370 => 
            array (
              'name' => 'ALPHATEC FRENCH BLUE GLOSS',
            ),
            371 => 
            array (
              'name' => 'DURALLOY OYSTER MATT',
            ),
            372 => 
            array (
              'name' => 'DURALLOY CHARCOAL GLOSS',
            ),
            373 => 
            array (
              'name' => 'ELECTRO MONUMENT FLAT',
            ),
            374 => 
            array (
              'name' => 'DURALLOY WALLABY MATT',
            ),
            375 => 
            array (
              'name' => 'PRIMER E-PRIME MATT',
            ),
            376 => 
            array (
              'name' => 'DURALLOY CLASSIC CREAM MATT',
            ),
            377 => 
            array (
              'name' => 'DURATEC ZEUS ARCTIC WHITE SATIN',
            ),
            378 => 
            array (
              'name' => 'ELECTRO BLUE GOLD FLAT',
            ),
            379 => 
            array (
              'name' => 'DURATEC INTENSITY EVERGREEN SATIN',
            ),
            380 => 
            array (
              'name' => 'DURALLOY BASALT SATIN',
            ),
            381 => 
            array (
              'name' => 'DURALLOY PEARL WHITE GLOSS',
            ),
            382 => 
            array (
              'name' => 'DURALLOY DUNE SATIN',
            ),
            383 => 
            array (
              'name' => 'DURALLOY EVENING HAZE SATIN',
            ),
            384 => 
            array (
              'name' => 'SURREAL EFFECTS HORIZON WHITE SCYLLA GLOSS RIPPLE',
            ),
            385 => 
            array (
              'name' => 'DURATEC ZEUS APPLIANCE WHITE SATIN',
            ),
            386 => 
            array (
              'name' => 'DURALLOY MAGNOLIA GLOSS',
            ),
            387 => 
            array (
              'name' => 'DURALLOY WHITE BIRCH GLOSS',
            ),
            388 => 
            array (
              'name' => 'ELECTRO BLUE GOLD FLAT',
            ),
            389 => 
            array (
              'name' => 'DURALLOY ANOTEC DARK GREY MATT',
            ),
            390 => 
            array (
              'name' => 'DURALLOY DEEP BRUNSWICK GREEN GLOSS',
            ),
            391 => 
            array (
              'name' => 'DURALLOY BERRY GREY SATIN',
            ),
            392 => 
            array (
              'name' => 'DURALLOY RIVERSAND MATT',
            ),
            393 => 
            array (
              'name' => 'DURATEC INTENSITY EVERGREEN SATIN',
            ),
            394 => 
            array (
              'name' => 'DURATEC INTENSITY LEAF SATIN',
            ),
            395 => 
            array (
              'name' => 'SURREAL EFFECTS MANNEX SILVER LODE PEARL COURSE TEXTURE',
            ),
            396 => 
            array (
              'name' => 'DURATEC ETERNITY LINEN PEARL SATIN',
            ),
            397 => 
            array (
              'name' => 'DURALLOY CLASSIC CREAM SATIN',
            ),
            398 => 
            array (
              'name' => 'DURALLOY MONUMENT MATT',
            ),
            399 => 
            array (
              'name' => 'ALPHATEC MISTLETOE GLOSS',
            ),
            400 => 
            array (
              'name' => 'ELECTRO BLUE NIGHT FLAT',
            ),
            401 => 
            array (
              'name' => 'DURATEC ZEUS TIMBERLAND SATIN',
            ),
            402 => 
            array (
              'name' => 'SURREAL EFFECTS LIGHT GREY SCYLLA GLOSS RIPPLE',
            ),
            403 => 
            array (
              'name' => 'ELECTRO BLUE NIGHT FLAT',
            ),
            404 => 
            array (
              'name' => 'DURALLOY MONUMENT MATT',
            ),
            405 => 
            array (
              'name' => 'ALPHATEC POMMEL BLUE GLOSS',
            ),
            406 => 
            array (
              'name' => 'DURALLOY APO GREY SATIN',
            ),
            407 => 
            array (
              'name' => 'DURALLOY SHALE GREY MATT',
            ),
            408 => 
            array (
              'name' => 'DURATEC ELEMENTS BASALT FLAT',
            ),
            409 => 
            array (
              'name' => 'DURALLOY WOODLAND GREY SATIN',
            ),
            410 => 
            array (
              'name' => 'PRECIOUS MOTHER OF PEARL GLOSS',
            ),
            411 => 
            array (
              'name' => 'DURALLOY DUNE SATIN',
            ),
            412 => 
            array (
              'name' => 'PRECIOUS GUNMETAL KINETIC PEARL SATIN',
            ),
            413 => 
            array (
              'name' => 'DURALLOY BLACK SATIN',
            ),
            414 => 
            array (
              'name' => 'FLUOROSET ALLURE COIN SATIN',
            ),
            415 => 
            array (
              'name' => 'DURATEC ZEUS MATT CANVAS CLOTH MATT',
            ),
            416 => 
            array (
              'name' => 'DURATEC ZEUS MONUMENT SATIN',
            ),
            417 => 
            array (
              'name' => 'PRECIOUS ESSENTIAL SILVER PEARL MATT',
            ),
            418 => 
            array (
              'name' => 'SURREAL EFFECTS AZTEC BLACK COLOUR VEIN RIPPLE',
            ),
            419 => 
            array (
              'name' => 'DURALLOY BERRY GREY GLOSS',
            ),
            420 => 
            array (
              'name' => 'DURALLOY DUNE MATT',
            ),
            421 => 
            array (
              'name' => 'SURREAL EFFECTS AZTEC SILVER COLOUR VEIN RIPPLE',
            ),
            422 => 
            array (
              'name' => 'PRIMERS ZINCSHIELD MATT',
            ),
            423 => 
            array (
              'name' => 'DURATEC ETERNITY CITI SILVER PEARL MATT',
            ),
            424 => 
            array (
              'name' => 'DURALLOY SURFMIST MATT',
            ),
            425 => 
            array (
              'name' => 'DURATEC ELEMENTS MAGNATITE FLAT',
            ),
            426 => 
            array (
              'name' => 'DURALLOY DEEP OCEAN MATT',
            ),
            427 => 
            array (
              'name' => 'FLUOROSET XTREME CHARCOAL MATT',
            ),
            428 => 
            array (
              'name' => 'DURALLOY OFF WHITE SATIN',
            ),
            429 => 
            array (
              'name' => 'ALPHATEC ANODIC BRONZE SATIN',
            ),
            430 => 
            array (
              'name' => 'DURATEC ELEMENTS WEATHERED STEEL FLAT',
            ),
            431 => 
            array (
              'name' => 'FLUOROSET XTREME BASALT MATT',
            ),
            432 => 
            array (
              'name' => 'DURATEC ZEUS DARK GREY MATT',
            ),
            433 => 
            array (
              'name' => 'ALPHATEC SAFETY YELLOW GLOSS',
            ),
            434 => 
            array (
              'name' => 'DURALLOY WEATHERED COPPER MATT',
            ),
            435 => 
            array (
              'name' => 'DURALLOY CLASSIC CREAM SATIN',
            ),
            436 => 
            array (
              'name' => 'DURALLOY STONE GREY SATIN',
            ),
            437 => 
            array (
              'name' => 'DURATEC ZEUS CHARCOAL SATIN',
            ),
            438 => 
            array (
              'name' => 'DURALLOY OYSTER MATT',
            ),
            439 => 
            array (
              'name' => 'CLEARCOAT BRIGHT SILVER METALLIC',
            ),
            440 => 
            array (
              'name' => 'PRECIOUS CHAMPAGNE KINETIC PEARL',
            ),
            441 => 
            array (
              'name' => 'DURALLOY STONE GREY SATIN',
            ),
            442 => 
            array (
              'name' => 'PRECIOUS CHARCOAL METALLIC PEARL',
            ),
            443 => 
            array (
              'name' => 'DURALLOY OLD PEWTER SATIN',
            ),
            444 => 
            array (
              'name' => 'DURATEC ELEMENTS NATURAL BRONZE FLAT',
            ),
            445 => 
            array (
              'name' => 'ELECTRO VENERABLE SILVER FLAT',
            ),
            446 => 
            array (
              'name' => 'SURREAL EFFECTS STONE BEIGE SCYLLA GLOSS RIPPLE',
            ),
            447 => 
            array (
              'name' => 'ELECTRO VENERABLE SILVER FLAT',
            ),
            448 => 
            array (
              'name' => 'PRECIOUS NICKEL PEARL MATT',
            ),
            449 => 
            array (
              'name' => 'DURATEC ETERNITY PEWTER PEARL SATIN',
            ),
            450 => 
            array (
              'name' => 'DURALLOY COVE SATIN',
            ),
            451 => 
            array (
              'name' => 'DURALLOY CLASSIC HAWTHORN GREEN GLOSS',
            ),
            452 => 
            array (
              'name' => 'DURALLOY TERRAIN SATIN',
            ),
            453 => 
            array (
              'name' => 'DURALLOY PAPERBARK MATT',
            ),
            454 => 
            array (
              'name' => 'FLUOROSET XTREME MONUMENT MATT',
            ),
            455 => 
            array (
              'name' => 'DURALLOY NOTRE DAME GLOSS',
            ),
            456 => 
            array (
              'name' => 'DURATEC ZEUS LUNAR GREY MATT',
            ),
            457 => 
            array (
              'name' => 'ELECTRO FLAT WHITE FLAT',
            ),
            458 => 
            array (
              'name' => 'ELECTRO MEDIUM BRONZE FLAT',
            ),
            459 => 
            array (
              'name' => 'DURALLOY CANOLA CREAM GLOSS',
            ),
            460 => 
            array (
              'name' => 'DURALLOY DOESKIN SATIN',
            ),
            461 => 
            array (
              'name' => 'ALPHATEC SIGNAL RED GLOSS',
            ),
            462 => 
            array (
              'name' => 'DURATEC ETERNITY STAR PEARL',
            ),
            463 => 
            array (
              'name' => 'DURATEC ETERNITY TITANIUM PEARL SATIN',
            ),
            464 => 
            array (
              'name' => 'PRECIOUS ONYX PEARL GLOSS',
            ),
            465 => 
            array (
              'name' => 'ELECTRO SILVER REIGN FLAT',
            ),
            466 => 
            array (
              'name' => 'DURALLOY STONE BEIGE MATT',
            ),
            467 => 
            array (
              'name' => 'PRECIOUS METROPOLIS BRONZE PEARL SATIN',
            ),
            468 => 
            array (
              'name' => 'SURREAL EFFECTS MANNEX BLACK COURSE TEXTURE',
            ),
            469 => 
            array (
              'name' => 'PRECIOUS NATURAL PEARL MATT',
            ),
            470 => 
            array (
              'name' => 'DURALLOY APO GREY SATIN',
            ),
            471 => 
            array (
              'name' => 'DURATEC INTENSITY MOONLIGHT SATIN',
            ),
            472 => 
            array (
              'name' => 'DURALLOY OLD PEWTER SATIN',
            ),
            473 => 
            array (
              'name' => 'DURATEC ZEUS SILVER GREY MATT',
            ),
            474 => 
            array (
              'name' => 'ELECTRO TIBERIUS FLAT',
            ),
            475 => 
            array (
              'name' => 'SURREAL EFFECTS APO GREY SCYLLA GLOSS RIPPLE',
            ),
            476 => 
            array (
              'name' => 'DURALLOY WILDERNESS SATIN',
            ),
            477 => 
            array (
              'name' => 'DURATEC INTENSITY SUNSHINE GLOSS',
            ),
            478 => 
            array (
              'name' => 'DURALLOY WOODLAND GREY SATIN',
            ),
            479 => 
            array (
              'name' => 'DURALLOY PEARL WHITE GLOSS',
            ),
            480 => 
            array (
              'name' => 'DURALLOY PRIMROSE GLOSS',
            ),
            481 => 
            array (
              'name' => 'DURATEC ZEUS LUNAR ECLIPSE SATIN',
            ),
            482 => 
            array (
              'name' => 'ALPHATEC MISTLETOE GLOSS',
            ),
            483 => 
            array (
              'name' => 'DURALLOY COTTAGE GREEN SATIN',
            ),
            484 => 
            array (
              'name' => 'ALPHATEC SPACE BLUE GLOSS',
            ),
            485 => 
            array (
              'name' => 'DURALLOY WEDGEWOOD SATIN',
            ),
            486 => 
            array (
              'name' => 'ELECTRO GOLD PEARL FLAT',
            ),
            487 => 
            array (
              'name' => 'ALPHATEC SAFETY YELLOW GLOSS',
            ),
            488 => 
            array (
              'name' => 'ALPHATEC FLAME RED GLOSS',
            ),
            489 => 
            array (
              'name' => 'DURATEC INTENSITY SUNSHINE GLOSS',
            ),
            490 => 
            array (
              'name' => 'ELECTRO BRILLIANCE FLAT',
            ),
            491 => 
            array (
              'name' => 'ARMOURSPRAY STREETWISE GLOSS',
            ),
            492 => 
            array (
              'name' => 'ELECTRO BRILLIANCE FLAT',
            ),
            493 => 
            array (
              'name' => 'DURALLOY WINDSPRAY SATIN',
            ),
            494 => 
            array (
              'name' => 'DURATEC INTENSITY REEF SATIN',
            ),
            495 => 
            array (
              'name' => 'DURATEC INTENSITY DESERT SATIN',
            ),
            496 => 
            array (
              'name' => 'PRECIOUS TYPHOON PEARL SATIN',
            ),
            497 => 
            array (
              'name' => 'ARMOURSPRAY BLACK  (ARMOURSPRAY SATIN',
            ),
            498 => 
            array (
              'name' => 'DURATEC INTENSITY REEF GLOSS',
            ),
            499 => 
            array (
              'name' => 'ALPHATEC BLAZE BLUE GLOSS',
            ),
            500 => 
            array (
              'name' => 'DURATEC INTENSITY COAST SATIN',
            ),
            501 => 
            array (
              'name' => 'DURATEC INTENSITY COAST SATIN',
            ),
            502 => 
            array (
              'name' => 'SURREAL EFFECTS STORM GREY SCYLLA GLOSS RIPPLE',
            ),
            503 => 
            array (
              'name' => 'DURALLOY DEEP OCEAN SATIN',
            ),
            504 => 
            array (
              'name' => 'SURREAL EFFECTS BLACK SCYLLA GLOSS RIPPLE',
            ),
            505 => 
            array (
              'name' => 'DURATEC ELEMENTS SURFMIST FLAT',
            ),
            506 => 
            array (
              'name' => 'DURALLOY PRIMROSE GLOSS',
            ),
            507 => 
            array (
              'name' => 'ALPHATEC LEMON YELLOW GLOSS',
            ),
            508 => 
            array (
              'name' => 'ALPHATEC TRANSFORMER GREY',
            ),
            509 => 
            array (
              'name' => 'DURALLOY SHOJI WHITE SATIN',
            ),
            510 => 
            array (
              'name' => 'DURATEC INTENSITY STORM SATIN',
            ),
            511 => 
            array (
              'name' => 'DURALLOY ANOTEC DARK GREY MATT',
            ),
            512 => 
            array (
              'name' => 'DURALLOY SURFMIST SATIN',
            ),
            513 => 
            array (
              'name' => 'DURALLOY SHOJI WHITE SATIN',
            ),
            514 => 
            array (
              'name' => 'ALPHATEC NAVY GLOSS',
            ),
            515 => 
            array (
              'name' => 'ARMOURSPRAY CLEAR',
            ),
            516 => 
            array (
              'name' => 'ELECTRO SENSATIONAL CHAMPAGNE FLAT',
            ),
            517 => 
            array (
              'name' => 'DURATEC ZEUS WHITE GLOSS',
            ),
            518 => 
            array (
              'name' => 'SURREAL EFFECTS NOTRE DAME SCYLLA GLOSS RIPPLE',
            ),
            519 => 
            array (
              'name' => 'ELECTRO BLACK ACE FLAT',
            ),
            520 => 
            array (
              'name' => 'DURALLOY WHITE SATIN',
            ),
            521 => 
            array (
              'name' => 'SURREAL EFFECTS MATT RIPPLE GRAPHITE SAROUK',
            ),
            522 => 
            array (
              'name' => 'DURALLOY CHARCOAL SATIN',
            ),
            523 => 
            array (
              'name' => 'PRECIOUS ST ELMOS FIRE KINETIC PEARL SATIN',
            ),
            524 => 
            array (
              'name' => 'DURATEC ZEUS CHALK USA GLOSS',
            ),
            525 => 
            array (
              'name' => 'PRECIOUS PLATYPUS KINETIC PEARL SATIN',
            ),
            526 => 
            array (
              'name' => 'DURALLOY BASALT MATT',
            ),
            527 => 
            array (
              'name' => 'DURALLOY ANOTEC OFF WHITE MATT',
            ),
            528 => 
            array (
              'name' => 'ALPHATEC SIGNAL RED GLOSS',
            ),
            529 => 
            array (
              'name' => 'DURATEC INTENSITY LEAF SATIN',
            ),
            530 => 
            array (
              'name' => 'DURALLOY WOODLAND GREY MATT',
            ),
            531 => 
            array (
              'name' => 'DURALLOY BARLEY GLOSS',
            ),
            532 => 
            array (
              'name' => 'DURALLOY PAPERBARK SATIN',
            ),
            533 => 
            array (
              'name' => 'DURALLOY GREY NURSE GLOSS',
            ),
            534 => 
            array (
              'name' => 'ALPHATEC ORANGE X15 GLOSS',
            ),
            535 => 
            array (
              'name' => 'DURATEC ETERNITY CHAMPAGNE KINETIC MATT',
            ),
            536 => 
            array (
              'name' => 'DURALLOY PEARL WHITE GLOSS',
            ),
            537 => 
            array (
              'name' => 'DURATEC ELEMENTS COPPER ORE FLAT',
            ),
            538 => 
            array (
              'name' => 'FLUOROSET ALLURE CHAMPAGNE SATIN',
            ),
            539 => 
            array (
              'name' => 'DURATEC ETERNITY COPPER COIN PEARL MATT',
            ),
            540 => 
            array (
              'name' => 'PRECIOUS METROPOLIS SILVER GLOW PEARL GLOSS',
            ),
            541 => 
            array (
              'name' => 'FLUOROSET XTREME WHITE SATIN',
            ),
            542 => 
            array (
              'name' => 'ELECTRO SILVER REIGN FLAT',
            ),
            543 => 
            array (
              'name' => 'ALPHATEC WHITE MATT',
            ),
            544 => 
            array (
              'name' => 'DURATEC ETERNITY SILVER KINETIC PEARL SATIN',
            ),
            545 => 
            array (
              'name' => 'DURALLOY WINDSPRAY MATT',
            ),
            546 => 
            array (
              'name' => 'SURREAL EFFECTS GREY HAMMER COLOUR VEIN RIPPLE',
            ),
            547 => 
            array (
              'name' => 'DURATEC ETERNITY CHARCOAL PEARL SATIN',
            ),
            548 => 
            array (
              'name' => 'DURALLOY DEEP BRUNSWICK GREEN GLOSS',
            ),
            549 => 
            array (
              'name' => 'DURALLOY WHITE BIRCH GLOSS',
            ),
            550 => 
            array (
              'name' => 'DURATEC ETERNITY NICKEL PEARL MATT',
            ),
            551 => 
            array (
              'name' => 'DURALLOY RIVERSAND MATT',
            ),
            552 => 
            array (
              'name' => 'DURATEC ELEMENTS BLACK (CB NSKY) FLAT',
            ),
            553 => 
            array (
              'name' => 'ALPHATEC ORANGE X GLOSS',
            ),
            554 => 
            array (
              'name' => 'FLUOROSET XTREME BLACK MATT',
            ),
            555 => 
            array (
              'name' => 'DURALLOY GULLY SATIN',
            ),
            556 => 
            array (
              'name' => 'SURREAL EFFECTS PRIMROSE SCYLLA GLOSS RIPPLE',
            ),
            557 => 
            array (
              'name' => 'DURALLOY JASPER SATIN',
            ),
            558 => 
            array (
              'name' => 'ELECTRO BASALT FLAT',
            ),
            559 => 
            array (
              'name' => 'DURALLOY RIVERGUM BEIGE GLOSS',
            ),
            560 => 
            array (
              'name' => 'PRECIOUS STEEL PEARL SATIN',
            ),
            561 => 
            array (
              'name' => 'DURALLOY MANGROVE MATT',
            ),
            562 => 
            array (
              'name' => 'DURALLOY SHALE GREY SATIN',
            ),
            563 => 
            array (
              'name' => 'SURREAL EFFECTS MANNEX WHITE COURSE TEXTURE',
            ),
            564 => 
            array (
              'name' => 'DURALLOY CLASSIC PEARL WHITE GLOSS',
            ),
            565 => 
            array (
              'name' => 'ELECTRO MEDIUM BRONZE FLAT',
            ),
            566 => 
            array (
              'name' => 'DURALLOY TERRAIN MATT',
            ),
            567 => 
            array (
              'name' => 'PRECIOUS BRONZE PEARL SATIN',
            ),
            568 => 
            array (
              'name' => 'DURALLOY COVE MATT',
            ),
            569 => 
            array (
              'name' => 'DURATEC ZEUS MONUMENT MATT',
            ),
            570 => 
            array (
              'name' => 'ELECTRO DARK BRONZE FLAT',
            ),
            571 => 
            array (
              'name' => 'SURREAL EFFECTS MANNEX SUEDE COURSE TEXTURE',
            ),
            572 => 
            array (
              'name' => 'DURALLOY GULLY MATT',
            ),
            573 => 
            array (
              'name' => 'DURALLOY JASPER MATT',
            ),
            574 => 
            array (
              'name' => 'DURATEC ZEUS BLACK MATT',
            ),
            575 => 
            array (
              'name' => 'ELECTRO TIBERIUS FLAT',
            ),
            576 => 
            array (
              'name' => 'DURATEC INTENSITY FLAME GLOSS',
            ),
            577 => 
            array (
              'name' => 'DURALLOY WHITE SATIN',
            ),
            578 => 
            array (
              'name' => 'DURALLOY SURFMIST SATIN',
            ),
            579 => 
            array (
              'name' => 'ARMOURSPRAY WHITE GLOSS',
            ),
            580 => 
            array (
              'name' => 'DURALLOY BRIGHT WHITE GLOSS',
            ),
            581 => 
            array (
              'name' => 'ARMOURSPRAY VELOCITY GLOSS',
            ),
            582 => 
            array (
              'name' => 'ELECTRO GOLD PEARL FLAT',
            ),
            583 => 
            array (
              'name' => 'DURATEC INTENSITY DESERT SATIN',
            ),
            584 => 
            array (
              'name' => '',
            ),
        );

        foreach($colours as $colour) {
            if (!Colours::where('name', $colour['name'])->first()) {
                Colours::create(['name' => $colour['name']]);
            }
        }

    }
}
