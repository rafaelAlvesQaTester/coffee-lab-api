# Teste de desempenho da API

## Objetivo

O script `tests/performance/smoke.js` verifica a disponibilidade e a resposta JSON dos endpoints somente de leitura `GET /health` e `GET /coffees`, medindo a latência HTTP observada pelo k6. Ele não cria cafés nem executa operações de escrita no PostgreSQL.

## Ambiente

- API local: `http://localhost:8083` por padrão.
- Ferramenta: k6, instalada separadamente pelo responsável pelo ambiente.
- Banco: a listagem consulta o PostgreSQL configurado pela API, mas não altera dados.
- Use preferencialmente um ambiente de teste representativo. O cenário SLO não deve ser executado contra produção sem autorização e planejamento.

Defina outro endereço-base com `BASE_URL`. O script remove barras finais do valor antes de formar os caminhos.

## Cenários e comandos

O cenário smoke é o padrão: 2 usuários virtuais por 30 segundos.

```powershell
k6 run .\tests\performance\smoke.js
```

O cenário SLO é separado e só é selecionado explicitamente. Executa 20 usuários virtuais constantes por cinco minutos:

```powershell
$env:K6_SCENARIO = "slo"
k6 run .\tests\performance\smoke.js
Remove-Item Env:K6_SCENARIO
```

Para apontar a execução a uma API de teste diferente:

```powershell
$env:BASE_URL = "http://localhost:8083"
k6 run .\tests\performance\smoke.js
Remove-Item Env:BASE_URL
```

O cenário SLO não é executado automaticamente. Se `K6_SCENARIO` não for informado, o script executa somente o cenário smoke; valores diferentes de `smoke` e `slo` são rejeitados.

## Verificações e thresholds

Cada iteração faz um GET a cada endpoint e verifica:

- `/health`: HTTP 200 e JSON com `status: "ok"` e `service: "coffee-lab-api"`.
- `/coffees`: HTTP 200 e corpo JSON que seja uma lista.

Os thresholds do script são:

- `http_req_duration`: `p(95)<500` ms.
- `successful_responses`: taxa de pelo menos 99% das respostas verificadas com sucesso (status e formato básico do JSON).

O SLO proposto é p95 abaixo de 500 ms com 20 usuários virtuais por cinco minutos. O threshold é aplicado também ao smoke curto, mas um smoke não demonstra o SLO de carga. A taxa `successful_responses` é calculada a partir das verificações combinadas de status e conteúdo em cada resposta.

## Métricas e limitações

k6 reporta, entre outras métricas, `http_req_duration` (latência observada pelo cliente), `http_reqs`, `iterations`, `checks`, `successful_responses`, `vus` e `dropped_iterations`. Essa latência inclui rede, Nginx, PHP e o acesso síncrono ao banco, sendo diferente de `duration_ms` dos logs PHP, que mede o processamento no servidor e não a transferência completa ao cliente.

Os resultados dependem do host que executa k6, da rede, da carga concorrente, do estado do PostgreSQL, do volume de dados e do aquecimento inicial. Um smoke curto é uma verificação operacional, não uma medição estatística robusta. Registre versão da ferramenta, endpoint, data, cenário, ambiente, resumo de métricas e falhas. Não reporte percentis se o k6 não tiver sido executado.

## Registro de evidências

Preencha após uma execução real; não use os campos como resultados presumidos.

| Campo | Evidência |
|---|---|
| Data/hora e fuso | Pendente |
| Commit testado | Pendente |
| Versão do k6 | Pendente |
| `BASE_URL` (sem credenciais) | Pendente |
| Cenário e duração | Pendente |
| Usuários virtuais | Pendente |
| Total de requisições | Pendente |
| p95 de `http_req_duration` | Pendente |
| Taxa `successful_responses` | Pendente |
| Resultado dos thresholds | Pendente |
| Erros e `dropped_iterations` | Pendente |

## Execuções registradas

### Smoke — 2 VUs por 30 segundos

Executado em 2026-11-14 no Windows 11/PowerShell contra `http://localhost:8083`, com k6 v2.4.0. O horário exato e o código de saída não foram preservados no registro desta execução.

| Métrica | Resultado |
|---|---:|
| VUs / duração | 2 / 30 s |
| Requisições | 5.832 |
| Throughput (`http_reqs`) | 194,32 req/s |
| `http_req_duration` p50 / p90 / p95 | 11,49 / 19,33 / 21,38 ms |
| `successful_responses` | 100% |
| Checks aprovados / reprovados | 5.832 / 0 |
| `http_req_failed` | 0% (0/5.832) |
| Thresholds | p95 `<500 ms` aprovado; sucesso `>=99%` aprovado |

### Cenário SLO — 20 VUs por 5 minutos

Executado uma vez em 2026-11-14 no Windows 11/PowerShell contra `http://localhost:8083`, com k6 v2.4.0. A coleta final foi registrada às 04:48:28 `-03:00`. O processo terminou com código de saída 0 e a variável `K6_SCENARIO` foi removida.

| Métrica | Resultado |
|---|---:|
| VUs / duração | 20 / 5 min |
| Iterações completas / interrompidas | 45.433 / 0 |
| Requisições | 90.866 |
| Throughput (`http_reqs`) | 302,74 req/s |
| `http_req_duration` p50 / p90 / p95 | 60,30 / 100,58 / 121,81 ms |
| `http_req_duration` média / mínimo / máximo | 65,67 / 5,38 / 1.390 ms |
| `successful_responses` | 100% (90.866/90.866) |
| Checks aprovados / reprovados | 90.866 / 0 |
| `http_req_failed` | 0% (0/90.866) |
| Thresholds | p95 `<500 ms` aprovado; sucesso `>=99%` aprovado |
| Código de saída do k6 | 0 |

Neste ambiente e nesta execução, o critério proposto de p95 abaixo de 500 ms foi atendido. O máximo observado foi 1,39 s; portanto, o resultado não significa que todas as requisições ficaram abaixo de 500 ms. A execução exercitou somente os GETs `/health` e `/coffees`; não fez escritas nem alterou dados PostgreSQL. Os resultados são uma medição local específica, não uma garantia de SLO em produção. O k6 não exibiu métrica de `dropped_iterations` no resumo final; não há valor a registrar.
