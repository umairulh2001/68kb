<script language="javascript" type="text/javascript">
	<!--
		function deleteSomething(url){
			removemsg = "<?php echo lang('kb_are_you_sure'); ?>"
			if (confirm(removemsg)) {
				document.location = url;
			}
		}
	// -->
</script>

<h2><?php echo lang('kb_manage_viewers'); ?> <a class="addnew" href="<?php echo site_url('admin/viewers/add');?>"><?php echo lang('kb_add_viewer'); ?></a></h2>

	<table class="main" width="100%" cellpadding="5" cellspacing="1">
			<tr>
				<th>
					<?php
						$class='';
						if(isset($orderby) && isset($opp) && $orderby=="id") {
							$class = $opp;
						}
					?>
					<?php if(isset($sort) && $sort == 'desc'): ?>
					<a href="<?php echo site_url('admin/viewers/grid/orderby/id/asc'); ?>" class="<?php echo $class; ?>">
					<?php else: ?>
					<a href="<?php echo site_url('admin/viewers/grid/orderby/id/desc'); ?>" class="<?php echo $class; ?>">
					<?php endif; ?>
					<?php echo lang('kb_id'); ?>
					</a>
				</th>
				<th>
					<?php
						$class='';
						if(isset($orderby) && isset($opp) && $orderby=="email") {
							$class = $opp;
						}
					?>
					<?php if(isset($sort) && $sort == 'desc'): ?>
					<a href="<?php echo site_url('admin/viewers/grid/orderby/email/asc'); ?>" class="<?php echo $class; ?>">
					<?php else: ?>
					<a href="<?php echo site_url('admin/viewers/grid/orderby/email/desc'); ?>" class="<?php echo $class; ?>">
					<?php endif; ?>
					<?php echo lang('kb_email'); ?>
					</a>
				</th>
				<th>
					<?php
						$class='';
						if(isset($orderby) && isset($opp) && $orderby=="lastname") {
							$class = $opp;
						}
					?>
					<?php if(isset($sort) && $sort == 'desc'): ?>
					<a href="<?php echo site_url('admin/viewers/grid/orderby/lastname/asc'); ?>" class="<?php echo $class; ?>">
					<?php else: ?>
					<a href="<?php echo site_url('admin/viewers/grid/orderby/lastname/desc'); ?>" class="<?php echo $class; ?>">
					<?php endif; ?>
					<?php echo lang('kb_name'); ?>
					</a>
				</th>
				<th>
					<?php
						$class='';
						if(isset($orderby) && isset($opp) && $orderby=="active") {
							$class = $opp;
						}
					?>
					<?php if(isset($sort) && $sort == 'desc'): ?>
					<a href="<?php echo site_url('admin/viewers/grid/orderby/active/asc'); ?>" class="<?php echo $class; ?>">
					<?php else: ?>
					<a href="<?php echo site_url('admin/viewers/grid/orderby/active/desc'); ?>" class="<?php echo $class; ?>">
					<?php endif; ?>
					<?php echo lang('kb_active'); ?>
					</a>
				</th>
				<th><?php echo lang('kb_actions'); ?></th>
			</tr>
			<?php  $alt = true;  foreach($items as $item): ?>
			<tr<?php if ($alt) echo ' class="second"'; else echo ' class="first"'; $alt = !$alt; ?>>
				<td><?php echo $item['id']; ?></td>
				<td><?php echo $item['email']; ?></td>
				<td><?php echo $item['lastname']; ?>, <?php echo $item['firstname']; ?></td>
				<td><?php echo ($item['active']) ? lang('kb_yes') : lang('kb_no'); ?></td>
				<td>
					<a href="<?php echo site_url('admin/viewers/edit/'.$item['id']); ?>"><img src="<?php echo base_url(); ?>images/page_edit.png" border="0" alt="<?php echo lang('kb_edit'); ?>" title="<?php echo lang('kb_edit'); ?>" /></a>
					&nbsp;
					<a href="javascript:void(0);" onclick="return deleteSomething('<?php echo site_url('admin/viewers/delete/'.$item['id']); ?>');"><img src="<?php echo base_url(); ?>images/page_delete.png" border="0" alt="<?php echo lang('kb_delete'); ?>" title="<?php echo lang('kb_delete'); ?>" /></a>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
	<div class="paginationNum">
		<?php echo lang('kb_pages'); ?>: <?php echo $pagination; ?>
	</div>
