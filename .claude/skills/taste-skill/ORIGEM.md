# Origem

Copiado de https://github.com/Leonxlnx/taste-skill (MIT), commit `ccbc156`,
de `skills/taste-skill/SKILL.md`. A licença original está em `LICENSE`.

## Regras que NÃO se aplicam a este projeto

A skill pressupõe React/Tailwind e projeto em inglês. Estas regras dela ficam
explicitamente suspensas aqui, e a razão:

- **"Ícones só de biblioteca (Phosphor/Tabler), nada de SVG à mão."** O site é
  estático e sem build; os 43 ícones inline de `build/icons.mjs` são a decisão
  certa. Importar uma biblioteca significaria uma dependência de runtime ou
  dezenas de requisições a mais.
- **"Zero travessões (—), sem exceção."** É uma regra de inglês. Em português o
  travessão é pontuação legítima ("avaliação de sistemas industriais — com
  conhecimento técnico"). O que vale aqui é a parte certa da regra: travessão
  como separador decorativo ("Chapecó — SC") vira vírgula ou barra.
- **Todo o pre-flight de React** (`use client`, `useEffect`, `min-h-[100dvh]`,
  `w-full px-4 max-w-7xl`): não há React neste projeto.
- **"Um design system (shadcn/Material/…)".** O sistema aqui são os tokens do
  próprio `css/style.css`.

O resto vale, e a contagem de eyebrows, os separadores decorativos e os
pontinhos de status já foram corrigidos a partir dela.
