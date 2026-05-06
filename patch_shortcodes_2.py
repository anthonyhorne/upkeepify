import sys

with open('includes/shortcodes.php', 'r') as f:
    content = f.read()

# 4. Formal quote logging
old_q = """    update_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_FORMAL_QUOTE, $quote );"""
new_q = """    update_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_FORMAL_QUOTE, $quote );

    upkeepify_log(
        'Contractor submitted formal quote',
        'info',
        array(
            'response_id' => $response_id,
            'quote_amount' => $quote,
        )
    );"""
content = content.replace(old_q, new_q)

# 5. Job completion logging
old_c = """    update_post_meta( $response_id, $completed_meta_key, time() );"""
new_c = """    update_post_meta( $response_id, $completed_meta_key, time() );

    upkeepify_log(
        $is_followup_completion ? 'Contractor submitted follow-up' : 'Contractor marked job as complete',
        'info',
        array(
            'task_id' => $task_id,
            'response_id' => $response_id,
        )
    );"""
content = content.replace(old_c, new_c)

# 6. Resident confirmation logging
old_rc = """    update_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED_AT, time() );"""
new_rc = """    update_post_meta( $task_id, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED_AT, time() );

    upkeepify_log(
        'Resident submitted feedback',
        'info',
        array(
            'task_id' => $task_id,
            'satisfied' => $satisfied,
        )
    );"""
content = content.replace(old_rc, new_rc)

with open('includes/shortcodes.php', 'w') as f:
    f.write(content)
