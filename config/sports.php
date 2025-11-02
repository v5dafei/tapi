<?php
/**
 * Created by PhpStorm.
 * User: thor
 * Date: 2022/3/11
 * Time: 23:29
 */

// 控制台服务器的地址和密钥
$conf['center_url'] = 'http://siteapi.fhptbet.net';
$conf['center_key']	= 'splj*(27&*9012kJKHhwd921gh78&Jjd91208&721bbhjs*&(0123865)*((&!^%hbhjqw';

// bet365模板
$conf['tpl_bet365_market_groups'] = [
    // 足球
    [
        'sport_id' => '1',
        'sport_name' => '足球',
        'market_groups' => [
            'asian_handicap_goal' => [
                'name' =>   '亚洲让分盘和大小盘',
                'market_group_ids' => [40, 938, 10143],
            ],
            'full_time_result' => [
                'name' =>   '全场赛果',
                'market_group_ids' => [40],
            ],
            'match_goals' => [
                'name' =>   '标准大小盘',
                'market_group_ids' => [10143],
            ],
        ],
    ],
    // 篮球
    [
        'sport_id' => '18',
        'sport_name' => '篮球',
        'market_groups' => [
            'basketball_game_lines' => [
                'name' =>   '比赛投注',
                'market_group_ids' => [1453],
            ],
        ],
    ],
    // 网球
    [
        'sport_id' => '13',
        'sport_name' => '网球',
        'market_groups' => [
            'tennis_to_win_match' => [
                'name' =>   '比赛获胜',
                'market_group_ids' => [83],
            ],
            'tennis_set_betting' => [
                'name' =>   '赛盘投注',
                'market_group_ids' => [358],
            ],
        ],
    ],
    // 排球
    [
        'sport_id' => '91',
        'sport_name' => '排球',
        'market_groups' => [
            'volleyball_game_lines' => [
                'name' =>   '比赛投注',
                'market_group_ids' => [910000],
            ],
        ],
    ],
    // 棒球
    [
        'sport_id' => '16',
        'sport_name' => '棒球',
        'market_groups' => [
            'baseball_game_lines' => [
                'name' =>   '比赛投注',
                'market_group_ids' => [1096],
            ],
        ],
    ],
    // 电竞
    [
        'sport_id' => '151',
        'sport_name' => '电竞',
        'market_groups' => [
            'esports_match_lines' => [
                'name' =>   '比赛投注',
                'market_group_ids' => [1510001],
            ],
        ],
    ],
];

$conf['stats_keys'] = [
    'goals'             => '进球数',
    'corners'           => '角球总数',
    'corner_f'          => '全场后角球',
    'corner_h'          => '半场后角球',
    'corner_ot'         => '加时后角球',
    'penalties'         => '点球',
    'offsides'          => '越位',
    'yellowcards'       => '黄牌',
    'redcards'          => '红牌',
    'yellowred_cards'   => '两黄变一红',
    'attacks'           => '进攻',
    'dangerous_attacks' => '危险进攻',
    'ball_safe'         => '安全球',
    'freekicks'         => '任意球',
    'goalattempts'      => '射门次数',
    'goalkicks'         => '球门球',
    'injuries'          => '受伤',
    'off_target'        => '射偏球门',
    'on_target'         => '射正球门',
    'possession_rt'     => '球权',
    'fouls'             => '犯规',
    'saves'             => '扑救',
    'shots_blocked'     => '阻止射门',
    'substitutions'     => '换人',
    'throwins'          => '界外球',
    '2points'           => '两分',
    'fouls'             => '犯规',
    'free_throws'       => '罚球',
    'free_throws_rate'  => '罚球得分率%',
    'time_outs'         => '暂停',
    'aces'              => '发球得分',
    'double_faults'     => '双发失误',
    'win_1st_serve'     => '首次发球得分',
    'break_point_conversions'     => '破发成功率%',
];


return $conf;