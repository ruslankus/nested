<?php if (isset($flashdata) && is_array($flashdata)) { ?>
    <div class="alert alert-<?php echo $flashdata[1] ?>">
    <button type="button" class="close" data-dismiss="alert">×</button>
        <?php echo $flashdata[2] ?>
    </div>
<?php } ?>

<form action="<?php echo $site_url ?>" name="ns" method="post" class="form-inline">
    Name: <input name="name" type="text" value="" />
    Def: <select name="def">
    <?php foreach($ary as $action):
        if ('move' !== mb_substr($action, 0, 4, 'utf-8')): ?>
        <option value="<?php echo $action ?>"><?php echo $action ?></option>
        <?php endif;
    endforeach; ?>
    </select>
    Cat: <select name="cat">
        <?php foreach($tree as $node): ?>
        <option value="<?php echo $node['id'] ?>"><?php echo h($node['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Send</button>
</form>

<?php // echo join('<br>', $ns_class->sql_queries); // debugDumpParams ?>

<br>

<table class="table table-bordered table-hover" style="width: 100%;">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Left</th>
            <th>Right</th>
            <th>Level</th>
            <th><div class="text-center">Actions</div></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($tree as $node): ?>
        <tr>
            <td><?php echo $node['id']; ?></td>

        <?php if (isset($_GET['def'], $_GET['id']) and $_GET['id'] == $node['id'] and 'move' === $_GET['def']) { ?>
            <td><span style="color: red;"><?php echo h($node['name']); ?></span></td>
        <?php } elseif(isset($_GET['def'], $_GET['id']) and $_GET['id'] != $node['id'] and 'move' === $_GET['def']) { ?>
            <td>
                <table class="table-condensed">
                    <tr>
                        <td style="border: none;"><a href="<?php echo $site_url ?>?def=moveToPrevSiblingOf&node=<?php echo $_GET['id']; ?>&parent=<?php echo $node['id']; ?>" class="btn btn-mini"><i class="icon-arrow-left"></i></a></td>
                        <td><a href="<?php echo $site_url ?>?def=moveToFirstChildOf&node=<?php echo $_GET['id']; ?>&parent=<?php echo $node['id']; ?>" class="btn btn-mini"><i class="icon-forward"></i></a></td>
                        <td><?php echo h($node['name']); ?></td>
                        <td><a href="<?php echo $site_url ?>?def=moveToLastChildOf&node=<?php echo $_GET['id']; ?>&parent=<?php echo $node['id']; ?>" class="btn btn-mini"><i class="icon-backward"></i></a></td>
                        <td><a href="<?php echo $site_url ?>?def=moveToNextSiblingOf&node=<?php echo $_GET['id']; ?>&parent=<?php echo $node['id']; ?>" class="btn btn-mini"><i class="icon-arrow-right"></i></a></td>
                    </tr>
                </table>
            </td>
        <?php } else { ?>
            <td><?php echo h($node['name']); ?></td>
        <?php } ?>
            
            <td><?php echo $node['left_key']; ?></td>
            <td><?php echo $node['right_key']; ?></td>
            <td><?php echo $node['depth']; ?></td>
            <td>
                <div class="text-center">
                <?php if (!isset($_GET['def'], $_GET['id']) or $_GET['id'] != $node['id'] or 'move' !== $_GET['def']): ?>
                    <a href="<?php echo $site_url ?>?def=move&id=<?php echo $node['id']; ?>" class="btn btn-mini"><i class="icon-move"></i></a>&nbsp;&nbsp;&nbsp;
                <?php endif; ?>
                    <a href="" class="btn btn-mini"><i class="icon-edit"></i></a>&nbsp;&nbsp;&nbsp;
                    <a href="<?php echo $site_url ?>?def=deleteNode&cat=<?php echo $node['id']; ?>" class="btn btn-mini" onclick="confirm('Вы уверены?');"><i class="icon-trash"></i></a>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
