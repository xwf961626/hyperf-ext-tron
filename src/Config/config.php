<?php

return [
    'jwt_scene' => 'default', // JWT 场景，可由宿主覆盖
    'example_key' => \Hyperf\Utils\Env::get('EXAMPLE_KEY', 'default_value'),
];
