const MOBILE_BREAKPOINT = 640;
const EMPTY_MONTH_LABEL = 'Нет данных';
const DEFAULT_SERIES_COLORS = ['#20c5b7', '#4f7ef7', '#8b5cf6', '#f59e0b', '#ef4444'];

const toNumber = (value) => {
  const parsed = Number.parseFloat(String(value));
  return Number.isFinite(parsed) ? parsed : 0;
};

const getIncomeChartData = () => {
  const raw = window.incomeChartData;
  if (!raw || typeof raw !== 'object') {
    return { months: [], series: [] };
  }

  return {
    months: Array.isArray(raw.months) ? raw.months : [],
    series: Array.isArray(raw.series) ? raw.series : [],
  };
};

const normalizeSeries = (seriesRaw) =>
  seriesRaw
    .filter((item) => item && typeof item === 'object')
    .map((item) => ({
      name: String(item.name ?? ''),
      data: Array.isArray(item.data) ? item.data.map((point) => toNumber(point)) : [],
    }))
    .filter((item) => item.name !== '');

const createIncomeBarChart = (el, months, series) => {
  const hasData = series.length > 0 && months.length > 0;
  const fallbackSeries = hasData ? series : [{ name: 'Нет данных', data: [0] }];
  const fallbackMonths = hasData ? months : [EMPTY_MONTH_LABEL];

  const options = {
    series: fallbackSeries,
    chart: {
      type: 'bar',
      height: 340,
      toolbar: { show: false },
    },
    colors: DEFAULT_SERIES_COLORS,
    plotOptions: {
      bar: {
        horizontal: false,
        borderRadius: 4,
        columnWidth: '48%',
      },
    },
    dataLabels: { enabled: false },
    stroke: {
      show: true,
      width: 1,
      colors: ['transparent'],
    },
    xaxis: {
      categories: fallbackMonths,
      labels: {
        style: { fontSize: '12px', fontFamily: 'Roboto, sans-serif' },
      },
    },
    yaxis: {
      labels: {
        formatter: (value) => `${toNumber(value).toFixed(0)}`,
        style: { fontSize: '12px', fontFamily: 'Roboto, sans-serif' },
      },
    },
    legend: {
      position: 'bottom',
      horizontalAlign: 'center',
      fontSize: '13px',
      fontFamily: 'Roboto, sans-serif',
    },
    fill: { opacity: 1 },
    tooltip: {
      y: {
        formatter: (value) => `${toNumber(value).toFixed(2)} ₽`,
      },
    },
    noData: {
      text: 'Нет данных за период',
    },
    responsive: [
      {
        breakpoint: MOBILE_BREAKPOINT,
        options: {
          chart: { height: 280 },
          plotOptions: {
            bar: { columnWidth: '65%' },
          },
          legend: { fontSize: '11px' },
        },
      },
    ],
  };

  const chart = new ApexCharts(el, options);
  chart.render();
};

const initIncomeChart = () => {
  if (typeof ApexCharts === 'undefined') {
    console.error('ApexCharts не загружен.');
    return;
  }

  const el = document.getElementById('incomeByCategoryChart');
  if (!(el instanceof HTMLElement)) {
    return;
  }

  try {
    const data = getIncomeChartData();
    const series = normalizeSeries(data.series);
    createIncomeBarChart(el, data.months, series);
  } catch (error) {
    console.error('Не удалось инициализировать график доходов.', error);
  }
};

requestAnimationFrame(initIncomeChart);
