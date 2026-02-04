/**
 * Order Notification System
 * Manages browser notifications for scheduled pizza orders
 */

class OrderNotificationManager {
    constructor() {
        this.storageKey = 'pizzamach_order_notifications';
        this.checkInterval = 60000; // Check every minute
        this.init();
    }

    init() {
        // Check if browser supports notifications
        if (!('Notification' in window)) {
            console.log('このブラウザは通知をサポートしていません');
            return;
        }

        // Request permission if not already granted
        if (Notification.permission === 'default') {
            this.requestPermission();
        }

        // Start checking for upcoming orders
        if (Notification.permission === 'granted') {
            this.checkUpcomingOrders();
            setInterval(() => this.checkUpcomingOrders(), this.checkInterval);
        }
    }

    async requestPermission() {
        try {
            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                this.showTestNotification();
            }
        } catch (error) {
            console.error('通知の許可リクエストに失敗しました:', error);
        }
    }

    showTestNotification() {
        new Notification('Pizza Mach 🍕', {
            body: '注文の通知を受け取れるようになりました！',
            icon: './assets/image/logo.png',
            badge: './assets/image/logo.png',
            tag: 'welcome',
            requireInteraction: false
        });
    }

    /**
     * Save order notification data to localStorage
     * Called from order_complete.php
     */
    saveOrderNotification(orderId, deliveryTime, orderDetails) {
        const notifications = this.getStoredNotifications();
        
        // Parse delivery time
        const deliveryDate = new Date(deliveryTime);
        const now = new Date();
        
        // Only save if delivery is in the future
        if (deliveryDate > now) {
            notifications.push({
                orderId: orderId,
                deliveryTime: deliveryTime,
                orderDetails: orderDetails,
                notified: false,
                createdAt: now.toISOString()
            });
            
            localStorage.setItem(this.storageKey, JSON.stringify(notifications));
            
            // Request permission if not granted yet
            if (Notification.permission === 'default') {
                this.requestPermission();
            }
        }
    }

    getStoredNotifications() {
        try {
            const stored = localStorage.getItem(this.storageKey);
            return stored ? JSON.parse(stored) : [];
        } catch (error) {
            console.error('通知データの取得に失敗しました:', error);
            return [];
        }
    }

    checkUpcomingOrders() {
        if (Notification.permission !== 'granted') {
            return;
        }

        const notifications = this.getStoredNotifications();
        const now = new Date();
        let updated = false;

        notifications.forEach(notification => {
            if (notification.notified) {
                return;
            }

            const deliveryTime = new Date(notification.deliveryTime);
            const timeDiff = deliveryTime - now;
            
            // Show notification 1 hour before delivery
            const oneHourBefore = 60 * 60 * 1000;
            
            // Check if delivery is tomorrow and it's evening (18:00-23:59)
            const isTomorrow = this.isTomorrow(deliveryTime, now);
            const isEvening = now.getHours() >= 18;
            
            // Show reminder if:
            // 1. It's tomorrow and evening time (evening reminder)
            // 2. It's 1 hour before delivery (pre-delivery reminder)
            if ((isTomorrow && isEvening && !notification.eveningReminded) || 
                (timeDiff > 0 && timeDiff <= oneHourBefore && !notification.preDeliveryReminded)) {
                
                const reminderType = (isTomorrow && isEvening) ? 'evening' : 'preDelivery';
                this.showOrderReminder(notification, reminderType);
                
                if (reminderType === 'evening') {
                    notification.eveningReminded = true;
                } else {
                    notification.preDeliveryReminded = true;
                }
                
                // Mark as fully notified if both reminders sent
                if (notification.eveningReminded && notification.preDeliveryReminded) {
                    notification.notified = true;
                }
                
                updated = true;
            }
            
            // Clean up old notifications (delivery time passed by more than 2 hours)
            if (timeDiff < -2 * 60 * 60 * 1000) {
                notification.notified = true;
                notification.expired = true;
                updated = true;
            }
        });

        if (updated) {
            // Remove expired notifications
            const activeNotifications = notifications.filter(n => !n.expired);
            localStorage.setItem(this.storageKey, JSON.stringify(activeNotifications));
        }
    }

    isTomorrow(date, now) {
        const tomorrow = new Date(now);
        tomorrow.setDate(tomorrow.getDate() + 1);
        
        return date.getDate() === tomorrow.getDate() &&
               date.getMonth() === tomorrow.getMonth() &&
               date.getFullYear() === tomorrow.getFullYear();
    }

    showOrderReminder(notification, type) {
        const deliveryTime = new Date(notification.deliveryTime);
        const timeStr = deliveryTime.toLocaleTimeString('ja-JP', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        let title, body;
        
        if (type === 'evening') {
            title = '明日のピザ注文をお忘れなく！ 🍕';
            body = `明日 ${timeStr} にご注文のピザをお届けします\n${notification.orderDetails || ''}`;
        } else {
            title = 'まもなくお届けです！ 🍕';
            body = `${timeStr} にピザをお届けします\n準備をお願いします！`;
        }
        
        const notif = new Notification(title, {
            body: body,
            icon: './assets/image/logo.png',
            badge: './assets/image/logo.png',
            tag: `order-${notification.orderId}-${type}`,
            requireInteraction: true,
            vibrate: [200, 100, 200]
        });

        // Open website when notification is clicked
        notif.onclick = function() {
            window.focus();
            this.close();
        };
    }

    /**
     * Request notification permission with user-friendly prompt
     */
    showPermissionPrompt() {
        if (!('Notification' in window)) {
            return;
        }

        if (Notification.permission === 'default') {
            // Show custom UI prompt before requesting permission
            const shouldRequest = confirm(
                '明日の注文を忘れないように、通知を受け取りますか？\n\n' +
                '通知を許可すると、配達の1時間前にお知らせします。'
            );
            
            if (shouldRequest) {
                this.requestPermission();
            }
        }
    }
}

// Initialize notification manager
const notificationManager = new OrderNotificationManager();

// Expose to global scope for use in other scripts
window.orderNotificationManager = notificationManager;
