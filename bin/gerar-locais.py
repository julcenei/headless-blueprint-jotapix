#!/usr/bin/env python3
"""
Gera jpx-eleicoes-2026/data/locais-<uf>-<ano>.json a partir dos dados abertos do TSE
(locais de votação), para mostrar o nome e o endereço de cada seção eleitoral.

Fonte: https://dadosabertos.tse.jus.br  (Eleitorado → Locais de votação)
  https://cdn.tse.jus.br/estatistica/sead/odsele/eleitorado_locais_votacao/eleitorado_local_votacao_<ano>.zip

Uso:
  python3 bin/gerar-locais.py eleitorado_local_votacao_2026_SC.csv sc 2026
"""
import csv
import json
import os
import sys


def main():
    if len(sys.argv) != 4:
        sys.exit(__doc__)
    arquivo, uf, ano = sys.argv[1], sys.argv[2].lower(), sys.argv[3]
    muns = {}
    gerado = ''
    with open(arquivo, encoding='latin-1', newline='') as f:
        for r in csv.DictReader(f, delimiter=';'):
            if r['SG_UF'].lower() != uf or r['NR_TURNO'] != '1':
                continue
            gerado = r['DT_GERACAO']
            m = muns.setdefault(r['CD_MUNICIPIO'].zfill(5), {'l': {}, 's': {}})
            local = r['NR_LOCAL_VOTACAO']
            m['l'][local] = [
                r['NM_LOCAL_VOTACAO'].strip(),
                r['DS_ENDERECO'].strip(),
                r['NM_BAIRRO'].strip(),
            ]
            # seção -> [local, eleitores, seção principal (agregada) ou 0]
            principal = int(r['NR_SECAO_PRINCIPAL']) if r['NR_SECAO_PRINCIPAL'] not in ('', '-1') else 0
            m['s'][f"{int(r['NR_ZONA'])}-{int(r['NR_SECAO'])}"] = [int(local), int(r['QT_ELEITOR_SECAO'] or 0), principal]

    destino = os.path.join(os.path.dirname(__file__), '..', 'jpx-eleicoes-2026', 'data', f'locais-{uf}-{ano}.json')
    os.makedirs(os.path.dirname(destino), exist_ok=True)
    with open(destino, 'w', encoding='utf-8') as f:
        json.dump({'fonte': 'TSE - dados abertos, locais de votação', 'gerado': gerado, 'mun': muns}, f, ensure_ascii=False, separators=(',', ':'))
    secoes = sum(len(m['s']) for m in muns.values())
    print(f'{destino}: {len(muns)} municípios, {secoes} seções')


if __name__ == '__main__':
    main()
