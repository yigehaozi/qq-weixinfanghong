<?php
/*
Plugin Name: 微信QQ内打开提示插件
Plugin URI: https://6.ke
Description: 当用户通过QQ或微信的内置浏览器访问网站时会出现一个弹窗蒙层告诉他需要使用系统自带浏览器打开此网址。
Version: 3.0
Author: 6ke论坛
Author URI: https://6.ke
*/

// 注册插件激活和停用钩子
register_activation_hook(__FILE__, 'browser_tip_activate');
register_deactivation_hook(__FILE__, 'browser_tip_deactivate');

// 激活插件时执行的函数
function browser_tip_activate() {
    add_option('browser_tip_display_page', 'settings');
}

// 停用插件时执行的函数
function browser_tip_deactivate() {
    // 在这里可以添加一些清理操作
}

// 注册钩子，将代码添加到网页底部
add_action('wp_footer', 'browser_tip_footer');

// 在网页底部添加代码
function browser_tip_footer() {
    // 判断当前请求是否来自QQ或微信的内置浏览器
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MQQBrowser') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
        // 获取当前显示的页面
        $display_page = get_option('browser_tip_display_page', 'card_key');

        // 如果当前显示的是卡密授权页面，并且用户已经进行了卡密授权，则将显示页面设置为设置页面
        if ($display_page == 'card_key' && browser_tip_is_card_key_authorized()) {
            update_option('browser_tip_display_page', 'settings');
        }

        // 如果当前显示的是设置页面，并且用户未进行卡密授权，则将显示页面设置为卡密授权页面
        if ($display_page == 'settings' && !browser_tip_is_card_key_authorized()) {
            update_option('browser_tip_display_page', 'card_key');
        }

        // 根据当前显示的页面决定是否显示卡密授权页面和设置页面
        if ($display_page == 'card_key' && !browser_tip_is_card_key_authorized()) {
            // 用户未进行卡密授权，显示卡密授权页面
            ob_start();
            ?>
            <div style='position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 9999;'></div>
            <div style='position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: #fff; padding: 20px; border-radius: 10px; z-index: 10000;'>
                <p style='font-size: 16px; line-height: 1.5em; margin-bottom: 20px;'>您还未进行卡密授权，请前往<a href='<?php echo admin_url('options-general.php?page=browser_tip_card_key'); ?>'>卡密授权页面</a>进行授权。</p>
            </div>
            <?php
            ob_end_flush();
        } else if ($display_page == 'settings' || ($display_page == 'card_key' && browser_tip_is_card_key_authorized())) {
            // 用户已经进行卡密授权，或者当前显示的是设置页面，显示设置页面
            // 获取插件设置项：提示信息和按钮文字
            $message = get_option('browser_tip_message', '请使用系统自带浏览器打开此网址');
            $copy_text = get_option('browser_tip_copy_text', '复制链接');
            $help_text = get_option('browser_tip_help_text', '如何操作');
            $help_content = get_option('browser_tip_help_content', '<ol style="font-size: 16px; line-height: 1.5em;"><li>在QQ或微信中打开本网址。</li><li>点击右上角的菜单按钮。</li><li>在弹出的菜单中选择“在浏览器中打开”或“在Safari中打开”。</li><li>等待浏览器加载页面。</li></ol>');

            // 输出HTML代码，实现弹窗蒙层效果
            ob_start();
            ?>
            <div id='browser-tip-mask' style='position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 9999;'>
                <div style='position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: #fff; padding: 20px; border-radius: 10px;'>
                    <p style='font-size: 16px; line-height: 1.5em; margin-bottom: 20px;'><?php echo $message; ?></p>
                    <div style='display: flex; justify-content: space-between; align-items: center;'>
                        <button id='browser-tip-copy' style='display: block; width: 140px; background-color: #007aff; color: #fff; border-radius: 5px; padding: 10px 0; border: none; font-size: 16px; text-align: center; cursor: pointer; margin-right: 10px;'><?php echo $copy_text; ?></button>
                        <button id='browser-tip-help' style='display: block; width: 140px; background-color: #007aff; color: #fff; border-radius: 5px; padding: 10px 0; border: none; font-size: 16px; text-align: center; cursor: pointer;'><?php echo $help_text; ?></button>
                    </div>
                </div>
            </div>
            <?php

            // 输出HTML代码，实现如何操作提示框效果
            ob_start();
            ?>
            <div id='browser-tip-help-box' style='display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: #fff; padding: 20px; border-radius: 10px; z-index: 10000;'>
                <h3 style='font-size: 20px; margin-bottom: 20px;'>如何使用系统自带浏览器打开此网址</h3>
                <?php echo $help_content; ?>
                <button id='browser-tip-help-close' style='display: block; width: 100%; background-color: #007aff; color: #fff; border-radius: 5px; padding: 10px 0; border: none; font-size: 16px; text-align: center; cursor: pointer; margin-top: 20px;'>关闭</button>
            </div>
            <?php

            // 输出JavaScript代码，实现点击复制链接按钮的逻辑
            ob_start();
            ?>
            <script>
                var copy = document.getElementById('browser-tip-copy');
                copy.addEventListener('click', function(event) {
                    event.preventDefault();
                    var url = window.location.href;
                    var input = document.createElement('input');
                    input.value = url;
                    document.body.appendChild(input);
                    input.select();
                    document.execCommand('copy');
                    document.body.removeChild(input);
                    var success = document.createElement('div');
                    success.innerHTML = '链接已复制到剪贴板';
                    success.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: #fff; padding: 20px; border-radius: 10px; z-index: 10000;';
                    document.body.appendChild(success);
                    setTimeout(function() {
                        document.body.removeChild(success);
                    }, 500);
                });
            </script>
            <?php

            // 输出JavaScript代码，实现点击如何操作按钮的逻辑
            ob_start();
            ?>
            <script>
                var help = document.getElementById('browser-tip-help');
                help.addEventListener('click', function(event) {
                    event.preventDefault();
                    document.getElementById('browser-tip-help-box').style.display = 'block';
                });
            </script>
            <?php

            // 输出JavaScript代码，实现关闭如何操作提示框的逻辑
            ob_start();
            ?>
            <script>
                var helpClose = document.getElementById('browser-tip-help-close');
                helpClose.addEventListener('click', function(event) {
                    event.preventDefault();
                    document.getElementById('browser-tip-help-box').style.display = 'none';
                });
            </script>
            <?php

            // 输出HTML、JavaScript代码
            ob_start();
            echo ob_get_clean();
            ob_end_flush();
        }
    }
}

// 判断用户是否进行了卡密授权
function browser_tip_is_card_key_authorized() {
    $card_key = get_option('browser_tip_card_key');
    if (!empty($card_key)) {
        // 判断当前用户是否已经进行过卡密授权
        $is_authorized = get_option('browser_tip_is_authorized', false);
        if ($is_authorized) {
            // 用户已经进行过卡密授权，直接返回 true
            return true;
        } else {
            // 调用卡密生成器 API 验证卡密是否有效
            $response = wp_remote_get('https://6.ke/wp-json/card_key_generator/v1/verify_card_key?card_key_code=' . $card_key);

            if (!is_wp_error($response)) {
                // 将 API 响应转换为数组
                $response_body = json_decode(wp_remote_retrieve_body($response), true);

                if ($response_body['success'] && $response_body['data']['is_authorized']) {
                    // 卡密授权成功，将授权标记设置为 true，并返回 true
                    update_option('browser_tip_is_authorized', true);
                    return true;
                }
            }
        }
    }

    return false;
}

// 注册钩子，添加插件设置页面
add_action('admin_menu', 'browser_tip_settings_menu');

// 添加插件设置页面
function browser_tip_settings_menu() {
    // 添加插件设置菜单
    add_menu_page('微信QQ内打开提示插件', '微信QQ内打开提示插件', 'manage_options', 'browser_tip', 'browser_tip_settings_page');

    // 添加插件设置子页面
    add_submenu_page('browser_tip', '插件设置', '插件设置', 'manage_options', 'browser_tip_settings', 'browser_tip_settings_page');

    // 添加插件授权子页面
    add_submenu_page('browser_tip', '插件授权', '插件授权', 'manage_options', 'browser_tip_card_key', 'browser_tip_card_key_page');

    // 注册设置项
    register_setting('browser_tip_settings_group', 'browser_tip_message');
    register_setting('browser_tip_settings_group', 'browser_tip_copy_text');
    register_setting('browser_tip_settings_group', 'browser_tip_help_text');
    register_setting('browser_tip_settings_group', 'browser_tip_help_content');
    register_setting('browser_tip_card_key_group', 'browser_tip_card_key');
}

// 插件设置页面
function browser_tip_settings_page() {
    ?>
    <div class="wrap">
        <h1>插件设置</h1>
        <form method="post" action="options.php">
            <?php settings_fields('browser_tip_settings_group'); ?>
            <?php do_settings_sections('browser_tip_settings_group'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="browser_tip_message">主弹窗提示语</label></th>
                    <td><input type="text" id="browser_tip_message" name="browser_tip_message" value="<?php echo esc_attr(get_option('browser_tip_message', '请使用系统自带浏览器打开此网址')); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="browser_tip_copy_text">左侧复制链接按钮名称</label></th>
                    <td><input type="text" id="browser_tip_copy_text" name="browser_tip_copy_text" value="<?php echo esc_attr(get_option('browser_tip_copy_text', '复制链接')); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="browser_tip_help_text">操作教程按钮名称</label></th>
                    <td><input type="text" id="browser_tip_help_text" name="browser_tip_help_text" value="<?php echo esc_attr(get_option('browser_tip_help_text', '如何操作')); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="browser_tip_help_content">操作教程（支持HTML）</label></th>
                    <td><textarea id="browser_tip_help_content" name="browser_tip_help_content" class="regular-text"><?php echo esc_attr(get_option('browser_tip_help_content', '<ol style="font-size: 16px; line-height: 1.5em;"><li>在QQ或微信中打开本网址。</li><li>点击右上角的菜单按钮。</li><li>在弹出的菜单中选择“在浏览器中打开”或“在Safari中打开”。</li><li>等待浏览器加载页面。</li></ol>')); ?></textarea></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
                <p>插件作者网站：<a href="https://6.ke" target="_blank">6ke论坛</a></p>
    </div>
    <?php
}

// 卡密授权页面
function browser_tip_card_key_page() {
    // 判断用户是否已经进行了卡密授权
    $is_authorized = get_option('browser_tip_is_authorized', false);

    if ($is_authorized) {
        // 用户已经进行了卡密授权，显示授权成功提示
        ?>
        <div class="wrap">
            <h1>插件授权</h1>
            <p>您已经成功授权使用本插件。</p>
        </div>
        <?php
    } else {
        // 用户还未进行卡密授权，显示卡密输入表单
        ?>
        <div class="wrap">
            <h1>插件授权</h1>
            <form method="post" action="options.php">
                <?php settings_fields('browser_tip_card_key_group'); ?>
                <?php do_settings_sections('browser_tip_card_key_group'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="browser_tip_card_key">请输入卡密</label></th>
                        <td><input type="text" id="browser_tip_card_key" name="browser_tip_card_key" value="<?php echo esc_attr(get_option('browser_tip_card_key')); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button('授权'); ?>
            </form>
     <p>插件作者网站：<a href="https://6.ke" target="_blank">6ke论坛</a></p>
        </div>
        <?php
    }
}
