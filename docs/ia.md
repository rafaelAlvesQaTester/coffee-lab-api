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

Esses resultados são evidências do ambiente local e não comprovam execução do GitHub Actions, cobertura de código medida ou desempenho em produção. O percentual de cobertura permanece pendente de medição.

## Revisão humana e responsabilidade

As alterações finais, a revisão do código, a execução de validações e a decisão de aceitar recomendações são de responsabilidade dos contribuidores do projeto. Nomes e atribuições individuais devem ser registrados em [CONTRIBUTORS.md](../CONTRIBUTORS.md) após confirmação; não são deduzidos a partir do uso de ferramentas.
