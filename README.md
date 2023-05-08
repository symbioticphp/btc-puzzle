## Verification Address Generator for Bitcoin puzzle range
```
Pool server   <-- Client.exe Request range

Pool server   --> Generate range
             
Pool server   --> Request signature adresses 
                 |----1Address0 <- Pool server  - 1 address from the pool server
                 |----1Address1 <- User server | 
                 |----1Address2 <- User server |- addresses from pool members
                 |----1Address3 <- User server |
                 |----1Address4 <- User server |
                 
Pool Server   --> Send to client
                { 
                 rangeStart: xxxxxxxxx,
                 rangeEnd  : xxxxxxxxx,
                 signatureAddresses : [1Address1, ...],
                }
               
Pool Server  <-- Client sending the found verification addresses to the pool
                { 
                 rangeStart: xxxxxxxxx,
                 rangeEnd  : xxxxxxxxx,
                 signature: ['privateHex1' => '1Address1',....],
                }
               
Pool Server  --> Check signature addresses and signatory range
       
```
To form the verification addresses of the issued range, several remote participant servers are used
enumeration, which eliminates the fake passage of the range even by the creator of the enumeration pool.

Each server, at its discretion, can keep logs of issued sectors, and after finding the puzzle, it is easy to check who
range was given.

### Algorithm for generating a verification address

To create a verification address, use:

- puzzle number
- sector number
- user ID of the user (for which the sector is issued)

The algorithm is simple, a hash is created based on the data and the first characters of the number are taken from it, which are substituted inside
range.
As an answer, a verification address and a hash of the sector are given, which is formed from a secret phrase, a puzzle and a number
sectors.

Example:

```
secret = efjiej34f9349gj4309hg4349tfh3044f3
puzzle: 66
sectorNumber = 32352000
userID = 1

We calculate:
                  hex( 2^(66 -1) +(2^40 * sectorNumber ) )
startHex        = 3eda7000000000000

                  hex( 2^(66 - 1) +(2^40 * sectorNumber + 1 ) )
nextHex         = 3eda7010000000000

                  hex( next - start) 
range           = ffffffffff   // 2^40 we need the number of characters
                 strlen( range )
addressHashLen  = 10

Sector signature for subsequent verification that the address was issued from your server.
                =  99980898683611033808737021fe85d29f08d02ef13076071348e330418ba8a4
sectorHash      = sha256(secret + puzzle + sectorNumber)

Sector hash for user
                sha256(secret + puzzle + sectorNumber + userID)
userSectorHash     = f87e6a01e99458e2121e1bf53985b8f98899cbc44458b53a1d627cf1381ea428

Address private key prefix
                 substr(startHex, addressHashLen (10) )
startHexPrefix = 3eda700 - remove characters equal to the length of the range

                 startHexPrefix +  substr(userSectorHash, 0, addressHashLen (10) )
addressHex     = 3eda700f87e6a01e9 - range prefix and 10 sector hash characters for the user

address        = 1BU1bkkW3YX1NyBxmaLpP48pe2uvh67jX

Answer:

'sectorHash' : '99980898683611033808737021fe85d29f08d02ef13076071348e330418ba8a4',
'address'     : '1BU1bkkW3YX1NyBxmaLpP48pe2uvh67jX'

```

### Usage

To use, you must create a secret phrase with a length of at least 20 characters. No one should know her otherwise
will be able to generate range addresses on your behalf.

The token is needed to protect against enumeration, otherwise each user will be able to enumerate all sectors and get verification
addresses before passing the ranges.

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

For convenient work, there is a controller that accepts GET parameters for operation:
```php
$controller = new \Symbiotic\BtcPuzzle\SignatureController($config, $_GET);

echo $controller->dispatch();
```

```php
// Verification address generator
$generator = new \Symbiotic\BtcPuzzle\SignatureGenerator($config); 

// Puzzle Range 
$sector = new \Symbiotic\BtcPuzzle\Sector($puzzleId, $sectorNumber);

// Create a verification address for the user
$address = $generator->generateSectorAddress($token, $sector, $user_id);

// hash of the sector for subsequent authentication of the issuance by the user's server
$sectorHash = $generator->getSectorHash($puzzleId, $sectorNumber);

echo json_encode([
'sectorHash' => $sectorHash,
 'address' => $address['address']/*we give only the address without the private key*/
 ]);

```






