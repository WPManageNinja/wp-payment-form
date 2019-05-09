<?php if($items): ?>
<table class="table wpf_table input_items_table table_bordered">
    <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
            <th><?php echo $item['label']; ?></th>
            <td><?php echo $item['value']; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>