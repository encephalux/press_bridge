<?php

require_once "env.php";

// { Theme supports }

add_theme_support("post-thumbnails");
add_theme_support("title-tag");

// { Helpers }

function send_cors_headers(): void
{
    header("Access-Control-Allow-Origin: " . _ALLOWED_ORIGIN_);
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
}

function notify(string $endpoint, array $data = []): bool|string
{
    $_data = json_encode(array_merge(['token' => _API_TOKEN_], $data));
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, _HOOK_PATH_BASE_ . $endpoint);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $_data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $result = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

    if ($result === false) error_log("[press_bridge] notify $endpoint failed: " . curl_error($curl));
    else if ($status >= 400) error_log("[press_bridge] notify $endpoint returned HTTP $status");

    curl_close($curl);

    return $result;
}

// { Actions }

function post_saved($id, $post, $is_update) : void
{
    if (!in_array($post->post_type, _SUPPORTED_POST_TYPES_, true)) return;

    $status = $post->post_status === "publish" ? "published" : "not_published";

    $endpoint = "/" . ($is_update ? "update" : "add");
    $thumbnail = get_the_post_thumbnail_url($post, "full");
    $data = [
        'post_type' => $post->post_type,
        'id' => $id,
        'slug' => $post->post_name,
        'title' => $post->post_title,
        'status' => $status,
        'date' => $post->post_date,
        'author' => get_the_author_meta("display_name", $post->post_author),
        'thumbnail' => $thumbnail === false ? "":$thumbnail
    ];

    if ($post->post_type !== "page") $data["excerpt"] = $post->post_excerpt;

    notify($endpoint, $data);
}

function post_deleted($id, $post) : void
{
    if (!in_array($post->post_type, _SUPPORTED_POST_TYPES_, true)) return;

    $endpoint = "/remove";
    $data = [
        'post_type' => $post->post_type,
        'id' => $id
    ];

    notify($endpoint, $data);
}

add_action("save_post", "post_saved", 10, 3);
add_action("delete_post", "post_deleted", 10, 2);

// { Ajax }

const _EMPTY_DATA_ = [
    'total' => 0,
    'list' => []
];

function load_post() : void
{
    send_cors_headers();
    header("Content-Type: application/json");

    $page = $_POST['page'] ?? 1;
    $per_page = $_POST['per_page'] ?? 5;
    $offset = $_POST['offset'] ?? 0;
    $post_type = $_POST['post_type'] ?? "post";

    foreach([$page, $per_page, $offset] as $item) {
        if (!preg_match("#^[0-9]+$#", (string) $item)) {
            echo json_encode(_EMPTY_DATA_);
            exit;
        }
    }

    $page = intval($page);
    $per_page = intval($per_page);
    $offset = intval($offset);

    if ($page < 1 || $per_page < 1 || !in_array($post_type, _SUPPORTED_POST_TYPES_, true)) {
        echo json_encode(_EMPTY_DATA_);
        exit;
    }

    $sql_offset = ($per_page * ($page - 1)) + $offset;

    global $wpdb;
    $result = $wpdb->get_results($wpdb->prepare(
        "select ID, post_name, post_title, post_excerpt, post_date, post_author from $wpdb->posts where post_type = %s and post_status = 'publish' order by post_date desc limit %d, %d",
        $post_type, $sql_offset, $per_page
    ), ARRAY_A);

    $stack = [];
    foreach($result as $post) {
        $stack[] = [
            'id' => $post['ID'],
            'slug' => $post['post_name'],
            'title' => $post['post_title'],
            'excerpt' => $post['post_excerpt'],
            'date' => $post['post_date'],
            'thumbnail' => get_the_post_thumbnail_url($post['ID'], "small"),
            'author' => get_the_author_meta("display_name", $post['post_author'])
        ];
    }

    echo json_encode([
        'total' => (int) wp_count_posts($post_type)->publish,
        'list' => $stack
    ]);

    exit;
}

add_action("wp_ajax_articles", "load_post");
add_action("wp_ajax_nopriv_articles", "load_post");

?>