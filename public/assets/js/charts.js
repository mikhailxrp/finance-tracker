const CURRENCY_SYMBOL = '₽';
const EMPTY_LABEL = 'Нет данных';
const EMPTY_COLOR = '#E5E7EB';
const MOBILE_BREAKPOINT = 640;

const INCOME_COLOR = '#16A34A';
const EXPENSE_COLOR = '#E76E88';

const toNumber = (value) => {
  const parsed = Number.parseFloat(String(value));
  return Number.isFinite(parsed) ? parsed : 0;
};

const formatCurrency = (value) => `${toNumber(value).toFixed(2)} ${CURRENCY_SYMBOL}`;

const formatAxisValue = (value) => {
  const num = toNumber(value);
  if (num >= 1_000_000) return `${(num / 1_000_000).toFixed(1)}М`;
  if (num >= 1_000) return `${(num / 1_000).toFixed(0)}k`;
  return String(num);
};

const getChartData = () => {
  const raw = window.chartData;
  if (!raw || typeof raw !== 'object') {
    return { expense_categories: [], daily_dynamics: [] };
  }
  return {
    expense_categories: Array.isArray(raw.expense_categories) ? raw.expense_categories : [],
    daily_dynamics: Array.isArray(raw.daily_dynamics) ? raw.daily_dynamics : [],
  };
};

const createDonutChart = (el, expenseCategories) => {
  const hasData = expenseCategories.length > 0;

  const series = hasData
    ? expenseCategories.map((item) => toNumber(item.amount))
    : [1];
  const labels = hasData
    ? expenseCategories.map((item) => String(item.category_name ?? EMPTY_LABEL))
    : [EMPTY_LABEL];
  const colors = hasData
    ? expenseCategories.map((item) => String(item.category_color ?? EMPTY_COLOR))
    : [EMPTY_COLOR];

  const options = {
    series,
    labels,
    colors,
    chart: {
      type: 'donut',
      height: 300,
      toolbar: { show: false },
      animations: { enabled: true, speed: 400 },
    },
    plotOptions: {
      pie: {
        donut: {
          size: '70%',
        },
      },
    },
    dataLabels: { enabled: false },
    legend: {
      position: 'bottom',
      fontSize: '13px',
      fontFamily: 'Roboto, sans-serif',
      markers: { size: 8 },
      formatter: (seriesName, opts) => {
        if (!hasData) return EMPTY_LABEL;
        const amount = toNumber(expenseCategories[opts.seriesIndex]?.amount ?? 0);
        return `${seriesName} &nbsp;<strong>${formatCurrency(amount)}</strong>`;
      },
    },
    tooltip: {
      enabled: hasData,
      y: {
        formatter: (val, opts) => {
          const item = expenseCategories[opts.seriesIndex] ?? null;
          const amount = toNumber(item?.amount ?? val);
          const pct = toNumber(item?.percentage ?? 0);
          return `${formatCurrency(amount)} (${pct.toFixed(1)}%)`;
        },
      },
    },
  };

  const chart = new ApexCharts(el, options);
  chart.render();
  return chart;
};

const createLineChart = (el, dailyDynamics) => {
  const labels = dailyDynamics.map((item) => {
    const d = new Date(String(item.date ?? ''));
    if (Number.isNaN(d.getTime())) return String(item.date ?? '');
    return new Intl.DateTimeFormat('ru-RU', { day: '2-digit', month: '2-digit' }).format(d);
  });

  const incomeData = dailyDynamics.map((item) => toNumber(item.income));
  const expenseData = dailyDynamics.map((item) => toNumber(item.expense));

  const options = {
    series: [
      { name: 'Доходы', data: incomeData },
      { name: 'Расходы', data: expenseData },
    ],
    chart: {
      type: 'area',
      height: 320,
      toolbar: { show: false },
      animations: { enabled: true, speed: 400 },
      zoom: { enabled: false },
    },
    colors: [INCOME_COLOR, EXPENSE_COLOR],
    fill: {
      type: 'gradient',
      gradient: {
        shadeIntensity: 1,
        opacityFrom: 0.15,
        opacityTo: 0.02,
        stops: [0, 100],
      },
    },
    stroke: {
      curve: 'smooth',
      width: 2,
    },
    markers: { size: 0 },
    dataLabels: { enabled: false },
    xaxis: {
      categories: labels,
      tickAmount: 8,
      labels: {
        rotate: 0,
        style: { fontSize: '11px', fontFamily: 'Roboto, sans-serif' },
      },
      axisBorder: { show: false },
      axisTicks: { show: false },
    },
    yaxis: {
      labels: {
        formatter: formatAxisValue,
        style: { fontSize: '11px', fontFamily: 'Roboto, sans-serif' },
      },
    },
    grid: {
      borderColor: 'rgba(0,0,0,0.06)',
      xaxis: { lines: { show: false } },
    },
    legend: {
      position: 'bottom',
      horizontalAlign: 'left',
      fontSize: '13px',
      fontFamily: 'Roboto, sans-serif',
      markers: { size: 8 },
    },
    tooltip: {
      x: { show: true },
      y: { formatter: (val) => formatCurrency(val) },
    },
    responsive: [
      {
        breakpoint: MOBILE_BREAKPOINT,
        options: {
          chart: { height: 220 },
          xaxis: { tickAmount: 4 },
          legend: { fontSize: '11px' },
        },
      },
    ],
  };

  const chart = new ApexCharts(el, options);
  chart.render();
  return chart;
};

const initCharts = () => {
  if (typeof ApexCharts === 'undefined') {
    console.error('ApexCharts не загружен.');
    return;
  }

  const donutEl = document.getElementById('expenseCategoriesChart');
  const lineEl = document.getElementById('dailyDynamicsChart');

  if (!(donutEl instanceof HTMLElement) || !(lineEl instanceof HTMLElement)) {
    return;
  }

  try {
    const data = getChartData();
    createDonutChart(donutEl, data.expense_categories);
    createLineChart(lineEl, data.daily_dynamics);
  } catch (error) {
    console.error('Не удалось инициализировать графики.', error);
  }
};

requestAnimationFrame(initCharts);
