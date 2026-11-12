const { Client } = require('ssh2');
const fs = require('fs');
const path = require('path');

const conn = new Client();

const filesToUpload = [
  {
    local: 'api/app/Filament/Pages/ManageSettings.php',
    remote: '/home/u174620650/domains/classybazarbd.com/laravel_api/app/Filament/Pages/ManageSettings.php'
  },
  {
    local: 'api/app/Filament/Resources/Orders/Schemas/OrderInfolist.php',
    remote: '/home/u174620650/domains/classybazarbd.com/laravel_api/app/Filament/Resources/Orders/Schemas/OrderInfolist.php'
  },
  {
    local: 'api/app/Filament/Resources/Orders/Tables/OrdersTable.php',
    remote: '/home/u174620650/domains/classybazarbd.com/laravel_api/app/Filament/Resources/Orders/Tables/OrdersTable.php'
  },
  {
    local: 'api/app/Http/Controllers/Api/SettingController.php',
    remote: '/home/u174620650/domains/classybazarbd.com/laravel_api/app/Http/Controllers/Api/SettingController.php'
  },
  {
    local: 'api/app/Http/Requests/StoreOrderRequest.php',
    remote: '/home/u174620650/domains/classybazarbd.com/laravel_api/app/Http/Requests/StoreOrderRequest.php'
  },
  {
    local: 'api/app/Http/Resources/OrderResource.php',
    remote: '/home/u174620650/domains/classybazarbd.com/laravel_api/app/Http/Resources/OrderResource.php'
  },
  {
    local: 'api/app/Models/Order.php',
    remote: '/home/u174620650/domains/classybazarbd.com/laravel_api/app/Models/Order.php'
  },
  {
    local: 'api/app/Services/OrderService.php',
    remote: '/home/u174620650/domains/classybazarbd.com/laravel_api/app/Services/OrderService.php'
  },
  {
    local: 'api/database/migrations/2026_11_12_150000_add_payment_fields_to_orders_table.php',
    remote: '/home/u174620650/domains/classybazarbd.com/laravel_api/database/migrations/2026_11_12_150000_add_payment_fields_to_orders_table.php'
  }
];

conn.on('ready', () => {
  console.log('SSH connected. Uploading Laravel backend files...');
  conn.sftp((err, sftp) => {
    if (err) throw err;
    let index = 0;
    function next() {
      if (index >= filesToUpload.length) {
        console.log('All backend files uploaded successfully!');
        runArtisan();
        return;
      }
      const item = filesToUpload[index++];
      console.log(`Uploading ${item.local} -> ${item.remote}`);
      sftp.fastPut(path.resolve(item.local), item.remote, (err) => {
        if (err) {
          console.error(`Error uploading ${item.local}:`, err);
        }
        next();
      });
    }
    next();
  });
}).connect({
  host: '217.21.87.110',
  port: 65002,
  username: 'u174620650',
  password: 'Classybazzar@1919'
});

function runArtisan() {
  console.log('Running artisan migrate & optimize:clear...');
  const cmd = 'cd /home/u174620650/domains/classybazarbd.com/laravel_api && php artisan migrate --force && php artisan optimize:clear';
  conn.exec(cmd, (err, stream) => {
    if (err) throw err;
    let out = '';
    stream.on('data', d => out += d);
    stream.stderr.on('data', d => out += d);
    stream.on('close', (code) => {
      console.log('Artisan result:\n' + out);
      conn.end();
    });
  });
}
