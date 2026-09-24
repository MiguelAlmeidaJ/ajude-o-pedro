# Ajude o Pedro — Rifa solidária

Sistema de rifa online feito em PHP + MySQL, pensado para hospedagem compartilhada e sem dependências de Composer.

## O que já existe

- Página pública responsiva com história, progresso, galeria e seleção de números.
- Reserva transacional para impedir duas pessoas de pegarem o mesmo número ao mesmo tempo.
- Reserva temporária de 30 minutos e liberação automática de números expirados.
- Pagamento por Pix com QR Code, Pix Copia e Cola e botão para copiar a chave.
- Confirmação manual do Pix pelo painel.
- Painel em `/admin`.
- Dois perfis:
  - `DEV`: cria novas campanhas, gerencia usuários e também edita campanhas.
  - `ADMIN`: visualiza e ajusta campanhas existentes e gerencia participações.
- CSRF, senhas com `password_hash`, sessão segura e consultas preparadas com PDO.

## Requisitos

- PHP 8.1 ou superior.
- MySQL 5.7+ ou MariaDB compatível.
- Extensões PHP: PDO MySQL, mbstring e iconv.
- Apache com `.htaccess` (recomendado).

## Instalação

1. Crie um banco MySQL e um usuário para ele no painel da hospedagem.
2. Edite `app/config.php` com host, banco, usuário e senha.
3. Envie todos os arquivos para a hospedagem.
4. Acesse `/setup.php`.
5. Crie o primeiro usuário DEV.
6. Entre em `/admin`.
7. Crie a campanha, configure:
   - valor por número;
   - quantidade de números;
   - chave Pix;
   - nome do recebedor;
   - cidade do recebedor;
   - meta;
   - imagens;
   - status `Ativa`.
8. Depois que o primeiro usuário existir, `setup.php` deixa de permitir nova instalação.

Se o projeto estiver em uma subpasta, ajuste `app.base_path` em `app/config.php`. Exemplo: `/rifa`.

## Imagens do Pedro

A campanha padrão do formulário usa estes caminhos como sugestão:

- `assets/img/pedro-familia.webp`
- `assets/img/pedro-1.webp`
- `assets/img/pedro-2.webp`
- `assets/img/pedro-3.webp`

Você pode subir as imagens nesses caminhos ou trocar os caminhos no editor da campanha.

## Fluxo da compra

1. Participante escolhe um ou mais números.
2. O sistema trava e reserva os números no banco.
3. É criado um pedido com token público.
4. O sistema gera o payload Pix com o valor exato do pedido.
5. O participante paga usando QR Code ou Pix Copia e Cola.
6. A equipe confere o recebimento e clica em **Confirmar** no painel.
7. Os números passam de reservados para pagos.
8. Se a reserva expirar sem confirmação, os números ficam disponíveis novamente.

## Observação sobre o Pix

O QR Code é gerado no navegador a partir de um payload BR Code montado pelo próprio sistema. Não existe integração automática com banco neste MVP, por isso a confirmação do recebimento é manual.

## Estrutura principal

```
/
├── admin/
│   ├── index.php
│   ├── login.php
│   ├── campanhas.php
│   ├── campanha.php
│   ├── pedidos.php
│   └── usuarios.php
├── app/
│   ├── auth.php
│   ├── bootstrap.php
│   ├── config.php
│   ├── csrf.php
│   ├── db.php
│   ├── helpers.php
│   ├── pix.php
│   └── raffle.php
├── assets/
│   ├── css/
│   └── js/
├── database/
│   └── schema.sql
├── index.php
├── pagamento.php
├── reservar.php
├── setup.php
└── status.php
```

## Antes de colocar no ar

- Use HTTPS.
- Troque os dados de banco em `app/config.php`.
- Configure e teste a chave Pix antes de ativar a campanha.
- Faça uma compra teste com valor baixo e confirme o QR Code no aplicativo do banco.
- Confira as regras aplicáveis à realização/divulgação da rifa solidária na sua localidade antes do lançamento público.
