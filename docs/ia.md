# Uso de ferramentas de inteligência artificial

## Ferramentas e escopo

ChatGPT e GitHub Copilot foram utilizados como apoio durante o planejamento, implementação, revisão e validação deste projeto. As áreas de trabalho assistidas incluíram:

- arquitetura e organização da API;
- implementação e testes de endpoints e persistência;
- configuração e diagnóstico de Docker e PostgreSQL;
- CI, PHPUnit e cobertura;
- documentação OpenAPI e documentação de arquitetura;
- logs estruturados, correlação de requisições e métrica de duração;
- preparação e execução de cenários k6.

Esta lista descreve categorias de assistência, não uma transcrição de prompts ou um registro exato de autoria por arquivo. Prompts literais, datas individuais, histórico completo das interações e atribuições humanas detalhadas não estão consolidados neste documento e não são inferidos.

## Como interpretar as recomendações

Sugestões, exemplos de código, diagnósticos e planos produzidos por ferramentas de IA foram tratados como material de apoio, sujeito à inspeção e validação no repositório e no ambiente local. Uma recomendação da ferramenta, por si só, não é evidência de que uma alteração funcionou ou de que uma propriedade foi verificada.

## Resultados verificados separadamente

Os seguintes resultados foram executados e registrados independentemente das recomendações da IA:

- PHPUnit unitário: 40 testes aprovados e 86 asserções.
- PHPUnit de integração PDO: 4 testes aprovados e 13 asserções, usando PostgreSQL de integração isolado.
- API local: endpoints REST e persistência PostgreSQL validados; logs JSON e `X-Request-Id` verificados.
- k6 v2.4.0: smoke com 2 VUs por 30 segundos, p95 de 21,38 ms; cenário local com 20 VUs por 5 minutos, p95 de 121,81 ms e 100% de respostas verificadas com sucesso.

Os resultados de API, integração PDO e k6 são evidências do ambiente local; os testes de desempenho não foram executados em produção. Separadamente, as execuções GitHub Actions CI #1 e CI #2 foram concluídas com sucesso, incluindo os jobs PHP/unit e OpenAPI. O relatório Clover do escopo unitário do CI registrou 176 de 182 linhas cobertas (96,70%), acima do mínimo configurado de 70%. Esse percentual se limita ao escopo unitário configurado e não representa cobertura de toda a aplicação.

## Revisão humana e responsabilidade

As alterações finais, a revisão do código, a execução de validações e a decisão de aceitar recomendações são de responsabilidade humana. A autoria e a participação individual estão registradas em [CONTRIBUTORS.md](../CONTRIBUTORS.md); ChatGPT e GitHub Copilot são ferramentas de apoio, não integrantes da equipe.
