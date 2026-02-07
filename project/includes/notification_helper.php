<?php
// includes/notification_helper.php
require_once 'models/Notification.php';

class NotificationHelper {
    
    // Создание уведомления при новой заявке
    public static function createForNewRequest($request_id, $user_name, $user_address) {
        // Уведомление для жителя
        Notification::createRequestNotification(
            $request_id, // user_id будет передан из контроллера
            $request_id,
            'Заявка создана',
            "Ваша заявка #{$request_id} успешно создана и принята в обработку"
        );
        
        // Уведомления для всех сотрудников УК
        Notification::createNewRequestNotification(
            $request_id,
            $user_name,
            $user_address
        );
        
        return true;
    }
    
    // Создание уведомления при изменении статуса заявки
    public static function createForRequestStatusChange($request_id, $user_id, $status, $assigned_to = null) {
        $status_messages = [
            'новая' => 'Заявка создана',
            'в работе' => 'Заявка переведена в работу',
            'выполнена' => 'Заявка выполнена',
            'отклонена' => 'Заявка отклонена'
        ];
        
        $title = $status_messages[$status] ?? 'Статус заявки изменен';
        $message = "Статус вашей заявки #{$request_id} изменен на: {$status}";
        
        $notification = new Notification();
        $notification->user_id = $user_id;
        $notification->title = $title;
        $notification->message = $message;
        $notification->type = 'info';
        $notification->related_type = 'request';
        $notification->related_id = $request_id;
        
        return $notification->create();
    }
    
    // Создание уведомления для сотрудника при назначении заявки
    public static function createForRequestAssignment($request_id, $employee_id, $user_name) {
        $notification = new Notification();
        $notification->user_id = $employee_id;
        $notification->title = 'Новая заявка назначена';
        $notification->message = "Вам назначена заявка #{$request_id} от {$user_name}";
        $notification->type = 'warning';
        $notification->related_type = 'request';
        $notification->related_id = $request_id;
        
        return $notification->create();
    }
    
    // Создание системного уведомления
    public static function createSystemNotification($user_id, $title, $message) {
        $notification = new Notification();
        $notification->user_id = $user_id;
        $notification->title = $title;
        $notification->message = $message;
        $notification->type = 'info';
        $notification->related_type = 'system';
        
        return $notification->create();
    }
}
?>