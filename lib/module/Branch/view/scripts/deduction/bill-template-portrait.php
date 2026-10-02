<?php
?>
<table cellpadding="2" cellspacing="0" border="0" style="margin:10px 10px 0 10px;" width="90%">
    <thead>
        <tr>
            <th class="main-heading center" colspan="2">
                FISHERMEN WELFARE SOCIETY
            </th>
        </tr>
        <tr>
            <th class="center" colspan="2">
                (Q519/83) Kollam. Phone : 2745188
            </th>
        </tr>
        <tr>
            <th class="center" colspan="2">
                Medicine Marketing Center : <?php echo $this->view->escapeHtml($fmc->name); ?> (<?php echo $this->view->escapeHtml($fmc->code); ?>)
            </th>
        </tr>
        <tr>
            <th class="center" colspan="2">
                <span style="font-size: 11pt;">Deduction Bill</span>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="50%">No. : <?php echo $id; ?></td>
            <td class="right">Date : <?php echo $this->view->datepicker($auctionChit->auction_date); ?></td>
        </tr>
        <tr>
            <td>Name : <?php echo $this->view->escapeHtml($member->name); ?></td>
            <td class="right">Code Number : <?php echo $this->view->escapeHtml($member->code); ?></td>
        </tr>
        <!--tr>
            <td>
                Society : <?php echo $this->view->escapeHtml($fmc->name); ?>
            </td>
            <td class="right">
                <span style="margin-right: 70px;">
                    Bill Number : &nbsp; &nbsp; &nbsp; &nbsp; Date : &nbsp;
                </span>
            </td>
        </tr-->

        <!-- Parent wrapper with two side-by-side columns -->
        <tr>
            <td colspan="2">
                <table cellpadding="4" cellspacing="0" border="0" width="100%">
                    <tr>
                        <!-- LEFT column: Deductions -->
                        <td width="60%" valign="top">
                            <table cellpadding="2" cellspacing="0" border="1" width="100%">
                                <thead>
                                    <tr>
                                        <th class="right">Bill Amount</th>
                                        <th class="right"><?php echo $auctionChit->amount; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($filtered as $groupId => $items): ?>
                                    <?php if (! empty($items)): ?>
                                    <!--tr>
                                        <td colspan="2"><?php echo $this->view->lookup('deductionGroup', $groupId); ?></td>
                                    </tr-->
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo $this->view->lookup('deductionType', $item->deduction_type_id); ?></td>
                                        <td class="right"><?php echo $item->deduction_amount; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </td>

                        <!-- RIGHT column: Totals -->
                        <td width="40%" valign="top">
                            <table cellpadding="4" cellspacing="0" border="1" width="100%">
                                <tbody>
                                    <tr>
                                        <td>Total deductions</td>
                                        <td class="right"><?php echo $auctionChit->deduction_amount; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Advance</td>
                                        <td class="right"><?php echo $auctionChit->advance; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Member Paid</td>
                                        <td class="right">
                                            <?php if ($auctionChit->member_paid_amount > 0) {
                                                echo $auctionChit->member_paid_amount;
                                            } else { ?>&nbsp;<?php } ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Balance</td>
                                        <td class="right"><?php echo $auctionChit->deduction_balance; ?></td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Total Amount</th>
                                        <th class="right"><?php echo $auctionChit->amount; ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <table cellpadding="2" cellspacing="0" border="0" width="100%">
                    <tbody>
                        <tr><td colspan="2">&nbsp;</td></tr>
                        <tr>
                            <td width="40%">
                                <span style="font-size: 15pt;"><?php echo $this->view->symbol('inr'); ?></span>
                                <span style="border-bottom: 3px double;">
                                    &nbsp; <strong><?php echo $auctionChit->deduction_balance; ?></strong>/- &nbsp;
                                </span>
                            </td>
                            <td>
                                Signature of recipient &nbsp;
                                <span class="dotted" style="white-space: pre;">                                            </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>
<?php ?>