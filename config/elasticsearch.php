<?php
return [
    'indices' => [
        'mappings' => [
            'video_index' => [
                "properties"=>  [
                    "name"=>  [
                        "type"=>  "text",
                        "analyzer"=>  "ik_max_word",
                        "search_analyzer"=>  "ik_smart"
                    ],
                    /*"description"=>  [
                        "type"=>  "text",
                        "analyzer"=>  "ik_max_word",
                        "search_analyzer"=>  "ik_smart"
                    ],*/
                    "title"=>  [
                        "type"=>  "text",
                        "analyzer"=>  "ik_max_word",
                        "search_analyzer"=>  "ik_smart"
                    ]
                ]
            ],
            /*'user_video_index' => [
                "properties"=>  [
                    "name"=>  [
                        "type"=>  "text",
                        "analyzer"=>  "ik_max_word",
                        "search_analyzer"=>  "ik_smart"
                    ],
                    "description"=>  [
                        "type"=>  "text",
                        "analyzer"=>  "ik_max_word",
                        "search_analyzer"=>  "ik_smart"
                    ],
                    "title"=>  [
                        "type"=>  "text",
                        "analyzer"=>  "ik_max_word",
                        "search_analyzer"=>  "ik_smart"
                    ]
                ]
            ],
            'key_words_index' => [
                "properties"=>  [
                    "words"=>  [
                        "type"=>  "text",
                    ],
                    "description"=>  [
                        "type"=>  "text",
                    ],
                    "title"=>  [
                        "type"=>  "text",
                    ]
                ]
            ]*/
        ]
    ],
];
