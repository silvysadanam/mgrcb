<?php
?>
<table cellpadding="2" cellspacing="0" border="0" style="margin:10px 10px 0 10px;" width="145mm">
            <thead>
                <tr>
                    <th class="main-heading center" colspan="2">
                        ഫിഷർമെൻ വെൽഫെയർ സൊസൈറ്റി
                    </th>
                </tr>
                <tr>
                    <th class="center" colspan="2">
                        (Q519/83) കൊല്ലം. ഫോൺ : 2745188
                    </th>
                </tr>
                <tr>
                    <th class="center" colspan="2">
                        മത്സ്യ വിപണന കേന്ദ്രം ശാഖ : <?php echo $this->view->escapeHtml($fmc->code); ?>
                    </th>
                </tr>
                <tr>
                    <th class="center" colspan="2">
                        <span style="font-size: 12pt;">Deduction Bill</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td width="50%"><span class="malayalam">നമ്പർ </span>: <?php echo $id; ?></td>
                    <td class="right">തീയതി : <?php echo $this->view->datepicker($auctionChit->auction_date); ?></td>
                </tr>
                <tr>
                    <td><span class="malayalam"> പേര് </span>: <?php echo $this->view->escapeHtml($member->name); ?></td>
                    <td class="right"><span class="malayalam"> കോഡ് നമ്പർ </span>: <?php echo $this->view->escapeHtml($member->code); ?></td>
                </tr>
                <tr>
                    <td>
                       <span class="malayalam"> സംഘം </span>: <?php echo $this->view->escapeHtml($fmc->name); ?>
                        
                    </td>
                    <td class="right">
                        <span style="margin-right: 70px;">
                           <span class="malayalam"> ബിൽ നമ്പർ </span>: &nbsp; &nbsp; &nbsp; &nbsp; <span class="malayalam"> തീയതി </span>:  &nbsp;
                        </span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <table cellpadding="2" cellspacing="0" border="1" width="100%">
                            <thead>
                                <tr>
                                    <th class="right"><span class="malayalam"> ബിൽ തുക </span>&nbsp; </th>
                                    <th class="right"><?php echo $auctionChit->amount; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filtered as $groupId => $items): ?>
                                <?php if (! empty($items)): ?>
                                
                                <tr>
                                    <td colspan="2"><?php echo $this->view->lookup('deductionGroup', $groupId); ?></td>
                                </tr>
                                <?php foreach ($items as $item): ?>
                                
                                <tr>
                                    <td class="malayalam"><?php echo $this->view->lookup('deductionType', $item->deduction_type_id); ?></td>
                                    <td class="right"><?php echo $item->deduction_amount; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                                <?php endforeach; ?>
                                <tr>
                                    <td>&nbsp;</td><td>&nbsp;</td>
                                </tr>
                                <tr>
                                    <td><span class="malayalam"> ആകെ പിടിക്കുന്നത് </span></td>
                                    <td class="right"><?php echo $auctionChit->deduction_amount; ?></td>
                                </tr>
                                <tr>
                                    <td><span class="malayalam">
                                            ലേലക്കാരനിൽ നിന്നും പറ്റ് </span>
                                    </td>
                                    <td class="right"><?php echo $auctionChit->advance; ?></td>
                                </tr>
                                <tr>
                                    <td><span class="malayalam">
                                            ആഫീസിൽ അടയ്ക്കുന്നത് </span>
                                    </td>
                                    <td class="right"><?php if ($auctionChit->member_paid_amount > 0) { echo $auctionChit->member_paid_amount; } else { ?>&nbsp;<?php } ?></td>
                                </tr>
                                <tr>
                                    <td><span class="malayalam"> ആഫീസിൽ നിന്നും കൈപ്പറ്റുന്നത് </span></td>
                                    <td class="right"><?php echo $auctionChit->deduction_balance; ?></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th><span class="malayalam"> ആകെ തുക </span></th>
                                    <th class="right"><?php echo $auctionChit->amount; ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <table cellpadding="2" cellspacing="0" border="0" width="100%">
                            <tbody>
                                <tr>
                                    <td colspan="2">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td width="40%">
                                        <span style="font-size: 15pt;"><?php echo $this->view->symbol('inr'); ?></span>
                                        <span style="border-bottom: 3px double;">
                                            &nbsp; <strong><?php echo $auctionChit->deduction_balance; ?></strong>/- &nbsp; 
                                        </span>
                                    </td>
                                    <td><span class="malayalam">
                                            കൈപ്പറ്റുന്ന ആളിന്റെ ഒപ്പ്  </span>&nbsp; 
                                        <span class="dotted" style="white-space: pre;">                                            </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>

<?php
?>

