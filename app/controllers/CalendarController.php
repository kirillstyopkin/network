<?php

namespace app\controllers;

use app\models\CalendarSettingsForm;
use yii;
use yii\web\Controller;
use app\models\Event;
use app\models\Eventcomment;
use app\models\User;
use app\models\Userevent;
use app\models\AddEventForm;

class CalendarController extends PjaxController
{
    static function calendarData() {
        $events = Event::sortByStartDate();

        foreach ($events as $event) {
            $events_array[] = array(
                'title'  => $event->title,
                'start'  => $event->start_date.' '.$event->start_time,
                'end'    => $event->end_date.' '.$event->end_time,
                'allDay' => false
            );
        }

        if (isset($events_array)) {
            $events_json = json_encode($events_array);
        } else {
            $events_json = '';
        }

        return $events_json;
    }

    function actionCalendar() {
        if (Yii::$app->getUser()->getIsGuest()) {
            Yii::$app->getResponse()->redirect('@web');
            return false;
        }

        $events_json = self::calendarData();

        $id = Yii::$app->getUser()->getIdentity()->getId();
        $user = User::find($id);
        $gcal = $user->searchSetting('gcal_feed');

        return $this->render('calendar', array(
            'events_json' => $events_json,
            'gcal' => $gcal
        ));
    }

    function actionDropevent() {
        if (Yii::$app->getUser()->getIsGuest()) {
            Yii::$app->getResponse()->redirect('@web');
            return false;
        }

        $start_date = date("Y-m-d", strtotime($_POST['start']));
        $end_date = date("Y-m-d", strtotime($_POST['end']));

        $events = Event::findByTitle($_POST['title']);

        if ($events) {
            $events->start_date = $start_date;
            $events->end_date = $end_date;
            $events->save();
        }

        $events_json = self::calendarData();

        $id = Yii::$app->getUser()->getIdentity()->getId();
        $user = User::find($id);
        $gcal = $user->searchSetting('gcal_feed');

        return $this->render('calendar', array(
            'events_json' => $events_json,
            'gcal' => $gcal
        ));
    }

    function actionEvents() {
        if (Yii::$app->getUser()->getIsGuest()) {
            Yii::$app->getResponse()->redirect('@web');
            return false;
        }

        $events = Event::sortByStartDate();

        return $this->render('events', array(
            'events' => $events
        ));
    }

    function actionComment() {
        if (isset($_POST['event_id'])) {

            $event = Event::find($_POST['event_id']);

            $userId = Yii::$app->getUser()->getIdentity()->getId();

            $eventcomment = new Eventcomment;
            $eventcomment->user_id = $userId;
            $eventcomment->event_id = $_POST['event_id'];
            $eventcomment->body = $_POST['comment'];
            $eventcomment->save();
        }

        $this->layout = 'block';

        return $this->render('eventcomments', array(
            'event' => $event
        ));
    }

    function actionEventpage($id = null) {
        if (Yii::$app->getUser()->getIsGuest()) {
            Yii::$app->getResponse()->redirect('@web');
            return false;
        }

        if ($id != null) {
            $event = Event::find($id);
        } else if (isset($_POST['title'])) {
            date_default_timezone_set('Europe/Kiev');
            $date_start = date("Y-m-d", strtotime($_POST['start']));
            $date_end = date("Y-m-d", strtotime($_POST['end']));
            $event = Event::findByTitleAndDate($_POST['title'], $date_start, $date_end);
        }

        // Mark event as read
        $event->markAsRead(Yii::$app->getUser()->getIdentity()->id);

        if ($id == null) {
            echo $event->id;
        } else {
            return $this->render('eventpage', array(
                'event' => $event
            ));
        }
    }

    function actionAddevent() {
        if (Yii::$app->getUser()->getIsGuest()) {
            Yii::$app->getResponse()->redirect('@web');
            return false;
        }

        date_default_timezone_set('Europe/Kiev');

        if (isset($_POST['start']) && isset($_POST['end'])) {
            $start_date = date("Y-m-d", strtotime($_POST['start']));
            $end_date = date("Y-m-d", strtotime($_POST['end']));
        } else if (isset($_POST['date'])) {
            $start_date = date("Y-m-d", strtotime($_POST['date']));
            $end_date = date("Y-m-d", strtotime($_POST['date']));
        } else {
            $start_date = null;
            $end_date = null;
        }

        $eventForm = new AddEventForm();
        $eventForm->scenario = 'default';

        $users = User::find(Yii::$app->getUser()->getId());

        $this->layout = 'block';

        return $this->render('addevent', array(
            'model' => $eventForm,
            'users' => $users,
            'start_date' => $start_date,
            'end_date' => $end_date
        ));
    }

    function actionEditEvent() {
        if (Yii::$app->getUser()->getIsGuest()) {
            Yii::$app->getResponse()->redirect('@web');
            return false;
        }

        $eventForm = new AddEventForm();
        $eventForm->scenario = 'default';

        $users = User::getAll();

        if (isset($_POST['event_id'])) {
            //event edit from events list
            $event = Event::find($_POST['event_id']);
            $id = $_POST['event_id'];
        } else if (isset($_POST['title'])) {
            //event edit from calendar
            date_default_timezone_set('Europe/Kiev');
            $date_start = date("Y-m-d", strtotime($_POST['start']));
            $date_end = date("Y-m-d", strtotime($_POST['end']));
            $event = Event::findByTitleAndDate($_POST['title'], $date_start, $date_end);
            $id = $event->id;
        }

        $this->layout = 'block';

        return $this->render('editevent', array(
            'model' => $eventForm,
            'event' => $event,
            'users' => $users,
            'event_id' => $id
        ));
    }

    function actionSaveEvent() {
        if (Yii::$app->getUser()->getIsGuest()) {
            Yii::$app->getResponse()->redirect('@web');
            return false;
        }

        $eventForm = new AddEventForm();
        $eventForm->scenario = 'default';
        $eventForm->load($_POST);

        if (isset($_POST['param'])) {
            $eventForm->addEvent();
        } else {
            $eventForm->editEvent($_POST['id_event']);
        }

        $status = count($eventForm->errors) > 0 ? 'error' : 'ok';

        $result = array(
            'status' => $status,
            'errors' => $eventForm->errors,
            'user'   => $eventForm->toArray()
        );
        echo json_encode($result);
    }

    function actionDeleteevent() {
        if (Yii::$app->getUser()->getIsGuest()) {
            Yii::$app->getResponse()->redirect('@web');
            return false;
        }

        $event = Event::find($_POST['id']);

        $user_events = Userevent::findByEventId($_POST['id']);

        foreach($user_events as $ev) {
            $ev->delete();
        }

        $event_comments = Eventcomment::byEvent($_POST['id']);

        foreach($event_comments as $comment) {
            $comment->delete();
        }

        $event->delete();

        return Yii::$app->getResponse()->redirect('@web/calendar/events');
    }

    function actionSettings() {
        if (Yii::$app->getUser()->getIsGuest()) {
            Yii::$app->getResponse()->redirect('@web');
            return false;
        }

        $id = Yii::$app->getUser()->getIdentity()->getId();
        $user = User::find($id);
        $gcal = $user->searchSetting('gcal_feed');

        $calendarSettingsForm = new CalendarSettingsForm();

        if (isset($_POST['feed']) && $calendarSettingsForm->saveSettings()) {
            $message = 'Settings have been saved';
            $feed = $_POST['feed'];
        } else {
            $message = null;
            $feed = $gcal;
        }

        return $this->render('settings', array(
            'message' => $message,
            'gcal' => $feed
        ));
    }
}