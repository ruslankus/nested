<?php
/* @$items class News */
?>

<?php foreach($items as $item):?>
    <h2><?php echo $item->title;?></h2>
    <p><?php echo $item->text; ?></p>
    <p><?php echo $item->date; ?></p>
    <hr/>
<?php endforeach;?>