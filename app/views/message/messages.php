<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Sophie
 * Date: 18.07.13
 * Time: 0:07
 * To change this template use File | Settings | File Templates.
 */
use yii\helpers\Html;
use yii\widgets\ActiveForm;
echo '<h1>' . $conversationTitle . '</h1><br>';
echo '<ul class="inline">';
foreach ($conversationMembers as $member) {
    echo '<li>';
    echo html::a($member->first_name . ' ' . $member->last_name, '#', array('class' => 'btn btn-small disabled')) ;
    echo '</li>';
}
echo '<li>'. Html::a('Add user +', 'message/members/' . $conversationId, array('class' => 'btn btn-small btn-primary')).'</li></ul>';
echo '<br><table id="TableOfMessages" class="content">';
foreach ($messages as $message) {
    if($message->user->first_name || $message->user->last_name) {
        echo '<tr><td id="NamesOfUsersInTableOfMessages">' . html::a($message->user->first_name . ' ' . $message->user->last_name, '#', array('class' => 'btn btn-small disabled')).'</td>';
        echo '<td><p class="message left">' . $message->body . '</p></td></tr>';
    }
}
echo '<tr><td></td><td>';
$form = ActiveForm::begin(array('options' => array('class' => 'form-inline')));
echo $form->field($model, 'body')->input('text',array('class' => 'input-xxlarge',
                                                       'placeholder' => 'Write your message here' ));
echo Html::submitButton('Send', array('class' => 'btn btn-success'));
echo '</td></tr></table>';
ActiveForm::end();
/*
<form class="form-inline">
    <input class="input-xxlarge" type="text" placeholder="Write your message here">
    <button type="submit" class="btn btn-success">Send</button>
</form>
*/

