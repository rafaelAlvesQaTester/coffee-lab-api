import { check, group } from 'k6';
import { Rate } from 'k6/metrics';
import http from 'k6/http';

const baseUrl = (__ENV.BASE_URL || 'http://localhost:8083').replace(/\/+$/, '');
const selectedScenario = __ENV.K6_SCENARIO || 'smoke';

const scenarioSettings = {
  smoke: {
    executor: 'constant-vus',
    vus: 2,
    duration: '30s',
  },
  slo: {
    executor: 'constant-vus',
    vus: 20,
    duration: '5m',
  },
};

if (!Object.prototype.hasOwnProperty.call(scenarioSettings, selectedScenario)) {
  throw new Error(`Unsupported K6_SCENARIO "${selectedScenario}". Use "smoke" or "slo".`);
}

const successfulResponses = new Rate('successful_responses');

export const options = {
  scenarios: {
    api: scenarioSettings[selectedScenario],
  },
  thresholds: {
    http_req_duration: ['p(95)<500'],
    successful_responses: ['rate>=0.99'],
  },
};

function recordResponse(response, predicate, description) {
  const passed = predicate(response);
  check(response, {
    [description]: passed,
  });
  successfulResponses.add(passed);
}

export default function () {
  group('health', () => {
    const response = http.get(`${baseUrl}/health`);
    recordResponse(
      response,
      (res) => {
        if (res.status !== 200) {
          return false;
        }

        try {
          const body = res.json();
          return body.status === 'ok' && body.service === 'coffee-lab-api';
        } catch (_error) {
          return false;
        }
      },
      'GET /health returns 200 with the expected JSON',
    );
  });

  group('coffees', () => {
    const response = http.get(`${baseUrl}/coffees`);
    recordResponse(
      response,
      (res) => {
        if (res.status !== 200) {
          return false;
        }

        try {
          return Array.isArray(res.json());
        } catch (_error) {
          return false;
        }
      },
      'GET /coffees returns 200 with a JSON array',
    );
  });
}
