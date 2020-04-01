<div class="wppayform_wrap">
    <div class="wppayform_wrap_area">
        <div class="wppay_add_on_navigation">
            <ul>
                <li class="wppay_add_on_item <?php echo ($current_menu_item == 'payform_add_ons') ? 'pay_menu_item_active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>">
                      Modules
                    </a>
                </li>
                <?php foreach ($menus as $menu_index => $menu_title): ?>
                    <li class="wppay_add_on_item pay_add_on_item_<?php echo $menu_index; ?> <?php echo ($current_menu_item == $menu_index) ? 'pay_menu_item_active' : ''; ?>">
                        <a href="<?php echo $base_url; ?>&sub_page=<?php echo $menu_index; ?>">
                            <?php echo $menu_title; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="wppay_add_on_body pay_add_on_body_<?php echo $current_menu_item; ?>">
            <?php
                do_action('fluentform_addons_page_render_' . $current_menu_item);
            ?>
        </div>
        <div id="payformAddonModules">
            <pay_form_addon_modules></pay_form_addon_modules>
        </div>
    </div>
</div>