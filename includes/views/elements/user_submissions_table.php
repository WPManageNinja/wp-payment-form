<table class="table wpf_submissions_table wpf_striped_table wpf_table table_bordered">
    <thead>
    <th style="min-width: 60px"><?php _e('#', 'wppayform'); ?></th>
    <th style="min-width: 120px"><?php _e('Date', 'wppayform'); ?></th>
    <th><?php _e('Form', 'wppayform'); ?></th>
    <?php if($show_payments): ?>
    <th style="width: 100px"><?php _e('Total', 'wppayform'); ?></th>
    <th style="width: 90px"><?php _e('Status', 'wppayform'); ?></th>
    <?php endif; ?>
    <?php if ($show_url): ?>
        <th style="width: 90px"><?php _e('Actions', 'wppayform'); ?></th>
    <?php endif; ?>
    </thead>
    <tbody>

    <?php foreach ($submissions as $submission): ?>
        <tr>
            <td class="wpf_highlight"><?php echo $submission->id; ?></td>
            <td><?php echo date('d M, Y', strtotime($submission->created_at)); ?></td>
            <td><?php echo $submission->post_title; ?></td>
            <?php if($show_payments): ?>
            <td><?php echo wpPayFormFormatMoney($submission->payment_total, $submission->form_id); ?></td>
            <td><?php echo $submission->payment_status; ?></td>
            <?php endif; ?>
            <?php if ($show_url): ?>
                <td><a class="wpf_view_url" href="<?php echo add_query_arg('wpf_submission', $submission->submission_hash, $permalink); ?>">View</a></td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php if (!empty($load_css)): ?>
    <style type="text/css">
        .wpf_table {
            empty-cells: show;
            font-size: 14px;
            border: 1px solid #cbcbcb
        }

        .wpf_table td, .wpf_table th {
            border-left: 1px solid #cbcbcb;
            border-width: 0 0 0 1px;
            font-size: inherit;
            margin: 0;
            overflow: visible;
            padding: .5em 1em
        }

        .wpf_table td:first-child, .wpf_table th:first-child {
            border-left-width: 0
        }

        .wpf_table thead {
            background-color: #e3e8ee;
            color: #000;
            text-align: left;
            vertical-align: bottom
        }

        .wpf_table td {
            background-color: transparent
        }
        .wpf_striped_table tbody tr:nth-child(even) {
            background: #f8f8f9;
        }
        .wpf_table tfoot {
            border-top: 1px solid #cbcbcb;
        }
    </style>
<?php endif; ?>
