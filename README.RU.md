## Генератор проверочных адресов для биткойн-головоломки
```
Pool server   <-- Client.exe  Запрос диапазона

Pool server   --> Генерация диапазона
             
Pool server   --> Запрос проверочных адресов для диапазона
                 |----1Address1 <- Pool server  - 1 адрес от сервера пула
                 |----1Address2 <- User server | 
                 |----1Address3 <- User server |- адреса от пользователей пула
                 |----1Address4 <- User server |
                 |----1Address5 <- User server |
                 
Pool Server   --> Отправка диапазона и проверочных адресов клиенту
                { 
                 rangeStart: xxxxxxxxx,
                 rangeEnd  : xxxxxxxxx,
                 signatureAddresses : [1Address1, ...],
                }
               
Pool Server  <-- Отправка в пул разгаданных адресов для подписи прохождения диапазона
                { 
                 rangeStart: xxxxxxxxx,
                 rangeEnd  : xxxxxxxxx,
                 signature: ['privateHex1' => '1Address1',....],
                }
               
Pool Server  --> Проверка адресов и подписание диапазона
       
```


Для формирования проверочных адресов выдаваемого диапазона, используется несколько удаленных серверов участников
перебора, что исключает подделку прохождения диапазона даже создателем пула перебора.

Каждый сервер на свое усмотрение может вести логи выданных секторов, и после нахождения пазла легко проверить кому
выдавался диапазон.

### Алгоритм формирования проверочного адреса

Для создания проверочного адреса используется:

- номер пазла
- номер сектора
- user ID пользователя (для которого выписывается сектор)

Алгоритм прост, создается хеш на основе данных и из него берутся первые символы числа, которые подставляются внутрь
диапазона.
В качестве ответа отдается проверочный адрес и хеш сектора, который формируется из секретной фразы, пазла и номера
сектора.

Пример:

```
secret = efjiej34f9349gj4309hg4349tfh3044f3
puzzle: 66
sectorNumber = 32352000
userID = 1

Вычисляем:
                  hex( 2^(66 -1) +(2^40 * sectorNumber ) )
startHex        = 3eda7000000000000

                  hex( 2^(66 - 1) +(2^40 * sectorNumber + 1 ) )
nextHex         = 3eda7010000000000

                  hex( next - start) 
range           = ffffffffff   // 2^40 нам нужно количество символов
                 strlen( range )
addressHashLen  = 10


Хеш сектора для формирования пароля, его знают только выдавший сервер и пул
                      sha256(secret + puzzle + sectorNumber)
sectorHash         =  99980898683611033808737021fe85d29f08d02ef13076071348e330418ba8a4

Подпись сектора для последующей проверки что выдан адрес именно с вашего сервера.
                     sha256(secret + puzzle + sectorNumber + userID)
userSectorHash     = f87e6a01e99458e2121e1bf53985b8f98899cbc44458b53a1d627cf1381ea428

Префикс приватного ключа адреса
                 substr(startHex, addressHashLen (10) )
startHexPrefix = 3eda700 - убираем символы равные длине диапазона

                 startHexPrefix +  substr(userSectorHash, 0, addressHashLen (10) )
addressHex     = 3eda700f87e6a01e9 - префикс диапазона и 10 символов хеша сектора для пользователя

address        = 1BU1bkkW3YX1NyBxmaLpP48pe2uvh67jX

Формируем пароль
     Хеширем неопределенной количество раз 5-50, защита от перебора создателем пула
                      sha256(sectorHash + addressPrivHex) 
passwordSHA256      = bde3eafd6fd1ca80870601f32e0c2f592462e446f7c5139e4ee9eb9230a10609

Окончательный хеш пароля для проверки в BLOWFISH (алгоритм защищенный от перебора)

password = BLOWFISH( passwordSHA256 )


Answer:

'sectorHash' : '99980898683611033808737021fe85d29f08d02ef13076071348e330418ba8a4',
'address'     : '1BU1bkkW3YX1NyBxmaLpP48pe2uvh67jX'
'userSectorHash' : 'f87e6a01e99458e2121e1bf53985b8f98899cbc44458b53a1d627cf1381ea428',
'signatureBlowfish' => '$2y$10$n5Ehd0YUVvy1tW2nLaMRTu6TGE84FY2mCbFwMDndTk1yBSf2J7pui'


```

### Usage

Для использования необходимо создать секретную фразу длиной не меньше 20 символов. Ее никто не должен знать, иначе
смогут сами генерировать адреса диапазонов от вашего имени.

Токен необходим для защиты от перебора, иначе каждый пользователь сможет перебрать все сектора и получить проверочные
адреса до прохождения диапазонов.

```php
$config = new \Symbiotic\BtcPuzzle\Config(
    [
        // your secret phrase for generating signatures
        'secret' => 'efjiej34f9349gj4309hg4349tfh3044f3',
        // authorization token for generating a range signature
        'token' => 'Aich45vbdghbds'
    ]
);
```

Для удобной работы есть контроллер:
```php
$controller = new \Symbiotic\BtcPuzzle\SignatureController($config, $_GET);
```

```php
// Генератор проверочных адресов
$generator = new \Symbiotic\BtcPuzzle\SignatureGenerator($config); 

// Диапазон пазла 
$sector = new \Symbiotic\BtcPuzzle\Sector($puzzleId, $sectorNumber);

// Создание проверочного адреса для пользователя 
$address = $generator->generateSectorAddress($token, $sector, $user_id);

// хеш сектора для последующей проверки подлинности выдачи сервером пользователя
$sectorHash = $generator->getSectorHash($puzzleId, $sectorNumber);

echo json_encode([
'sectorHash' => $sectorHash,
 'address' => $address['address']/*отдаем адрес без приватного ключа*/
 ]);

```





