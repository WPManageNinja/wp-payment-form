<?php if ($items) : ?>
    <table class="table wpf_table input_items_table table_bordered">
        <tbody>
        <?php foreach ($items as $item) : ?>
            <?php if ($showEmpty || (isset($item['value']) && $item['value'] !== '' && isset($item['label']))) : ?>
                <tr>
                    <th><?php echo esc_html($item['label']); ?></th>
                        <td><?php
                        if (is_array($item['value'])) {
                            echo esc_html(implode(', ', array_map('strval', $item['value'])));
                        } else {
                            echo esc_html($item['value']);
                        }; ?>
                        </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>