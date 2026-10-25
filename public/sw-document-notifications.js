self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    const targetUrl = (event.notification.data && event.notification.data.url) || '/student-documents';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (let i = 0; i < clientList.length; i += 1) {
                const client = clientList[i];
                if ('focus' in client) {
                    client.focus();
                    if (targetUrl && 'navigate' in client) {
                        return client.navigate(targetUrl);
                    }
                    return client;
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }
        })
    );
});
