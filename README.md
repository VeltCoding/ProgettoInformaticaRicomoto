istruzioni in caso di apertura su altro pc:  
1) starta start.sh
2) runna il server sulla porta 8080
3) vai alla pagina localhost e metti /phpmyadmin per il phpmyadmin,  /ricomoto/dashboard per la dashboard di ricomoto.
4) per testare l'endpoint:

curl -i -X POST http://localhost/ricomoto/api/token.php \
  -H "Content-Type: application/json" \
  -d '{"email":"ilyasmoto@gmail.com","password":"admin"}'


curl -i http://localhost/ricomoto/api/permissions.php \
  -H "Authorization: Bearer INCOLLA_TOKEN"


**bisogna come prima cosa usare il file sql per importare il database**
