<?php if ($items): ?>
    <table class="table wpf_table input_items_table table_bordered">
        <tbody>
        <?php foreach ($items as $item): ?>
            <?php if (isset($item['value']) && isset($item['label'])): ?>
                <tr>
                    <th><?php echo $item['label']; ?></th>
                    <td><?php
                        if (is_array($item['value'])) {
                            echo implode(', ', $item['value']);
                        } else {
                            echo $item['value'];
                        } ; ?></td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>